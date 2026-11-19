<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

function blog_email_notifications_ready(): bool
{
    return SMTP_HOST !== ''
        && SMTP_PORT >= 1
        && SMTP_PORT <= 65535
        && (SMTP_USERNAME === '' || SMTP_PASSWORD !== '')
        && filter_var(SMTP_FROM_EMAIL, FILTER_VALIDATE_EMAIL) !== false;
}

function blog_push_notifications_ready(): bool
{
    return preg_match('/^[A-Za-z0-9_-]{80,100}$/', VAPID_PUBLIC_KEY) === 1
        && preg_match('/^[A-Za-z0-9_-]{40,50}$/', VAPID_PRIVATE_KEY) === 1
        && str_starts_with(VAPID_SUBJECT, 'mailto:')
        && filter_var(substr(VAPID_SUBJECT, 7), FILTER_VALIDATE_EMAIL) !== false;
}

function blog_notification_recipients(PDO $pdo, string $channel): array
{
    if ($channel === 'email') {
        return $pdo->query("SELECT u.id,u.name,u.email FROM blog_notification_preferences n INNER JOIN comment_users u ON u.id=n.user_id WHERE n.email_enabled=1 AND u.email<>''")->fetchAll();
    }

    return $pdo->query("SELECT s.id,s.user_id,s.endpoint_hash,s.endpoint,s.p256dh,s.auth_token FROM blog_push_subscriptions s INNER JOIN blog_notification_preferences n ON n.user_id=s.user_id WHERE n.push_enabled=1")->fetchAll();
}

function blog_send_new_post_notifications(PDO $pdo, array $post, string $url): array
{
    $result = ['email_sent'=>0,'email_failed'=>0,'push_sent'=>0,'push_failed'=>0];
    $emails = blog_notification_recipients($pdo, 'email');
    $subscriptions = blog_notification_recipients($pdo, 'push');

    if (!$emails && !$subscriptions) {
        return $result;
    }

    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        error_log('Blog notifications could not run: Composer dependencies are missing.');
        $result['email_failed'] = count($emails);
        $result['push_failed'] = count($subscriptions);
        return $result;
    }
    require_once $autoload;

    if ($emails) {
        if (!blog_email_notifications_ready()) {
            $result['email_failed'] = count($emails);
        } else {
            foreach ($emails as $recipient) {
                try {
                    $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
                    $mailer->isSMTP();
                    $mailer->Host = SMTP_HOST;
                    $mailer->Port = SMTP_PORT;
                    $mailer->SMTPAuth = SMTP_USERNAME !== '';
                    if ($mailer->SMTPAuth) {
                        $mailer->Username = SMTP_USERNAME;
                        $mailer->Password = SMTP_PASSWORD;
                    }
                    $mailer->SMTPSecure = SMTP_PORT === 465
                        ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                        : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mailer->CharSet = 'UTF-8';
                    $mailer->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                    $mailer->addAddress((string)$recipient['email'], (string)$recipient['name']);
                    $mailer->isHTML(true);
                    $mailer->Subject = 'Novo artigo no blog: ' . (string)$post['title'];
                    $safeTitle = htmlspecialchars((string)$post['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $safeExcerpt = htmlspecialchars((string)$post['excerpt'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $safeUrl = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $preferenceUrl = htmlspecialchars($url . '#blogNotificationForm', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $mailer->Body = '<p>Olá ' . htmlspecialchars((string)$recipient['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ',</p><p>Foi publicado um novo artigo no blog da ' . htmlspecialchars(APP_NAME, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ':</p><h2>' . $safeTitle . '</h2><p>' . $safeExcerpt . '</p><p><a href="' . $safeUrl . '">Ler artigo</a></p><p>Para deixar de receber estes avisos, <a href="' . $preferenceUrl . '">gerir as preferências</a> após entrar na sua conta Google.</p>';
                    $mailer->AltBody = "Novo artigo: {$post['title']}\n\n{$post['excerpt']}\n\nLer: {$url}\n\nGerir preferências: {$url}#blogNotificationForm";
                    $mailer->send();
                    $result['email_sent']++;
                } catch (Throwable $e) {
                    error_log('Blog notification email failed for account ' . (int)$recipient['id'] . ': ' . $e->getMessage());
                    $result['email_failed']++;
                }
            }
        }
    }

    if ($subscriptions) {
        if (!blog_push_notifications_ready()) {
            $result['push_failed'] = count($subscriptions);
        } else {
            try {
                $webPush = new Minishlink\WebPush\WebPush([
                    'VAPID' => [
                        'subject' => VAPID_SUBJECT,
                        'publicKey' => VAPID_PUBLIC_KEY,
                        'privateKey' => VAPID_PRIVATE_KEY,
                    ],
                ]);
                $payload = json_encode([
                    'title' => (string)$post['title'],
                    'body' => (string)$post['excerpt'],
                    'url' => $url,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

                foreach ($subscriptions as $subscriptionRow) {
                    try {
                        $subscription = Minishlink\WebPush\Subscription::create([
                            'endpoint' => (string)$subscriptionRow['endpoint'],
                            'keys' => [
                                'p256dh' => (string)$subscriptionRow['p256dh'],
                                'auth' => (string)$subscriptionRow['auth_token'],
                            ],
                        ]);
                        $report = $webPush->sendOneNotification($subscription, $payload, ['TTL'=>86400]);
                        if ($report->isSuccess()) {
                            $result['push_sent']++;
                        } else {
                            if ($report->isSubscriptionExpired()) {
                                $delete = $pdo->prepare('DELETE FROM blog_push_subscriptions WHERE id=?');
                                $delete->execute([(int)$subscriptionRow['id']]);
                            }
                            error_log('Blog push notification failed for subscription ' . (int)$subscriptionRow['id'] . ': ' . $report->getReason());
                            $result['push_failed']++;
                        }
                    } catch (Throwable $e) {
                        error_log('Blog push notification failed for subscription ' . (int)$subscriptionRow['id'] . ': ' . $e->getMessage());
                        $result['push_failed']++;
                    }
                }
            } catch (Throwable $e) {
                error_log('Blog push service could not be initialized: ' . $e->getMessage());
                $result['push_failed'] += count($subscriptions);
            }
        }
    }

    return $result;
}
