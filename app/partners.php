<?php

/**
 * app/partners.php
 * Código organizado e comentado para ser fácil de entender e manter.
 * Assinado por Flyzinho — toque próprio, feito com cuidado.
 */



/**
 * Lista os parceiros exibidos no site quando não existe uma configuração personalizada.
 */
function default_site_partners(): string
{
    return implode("\n", [
        'Flyzinho | assets/imagem/flyzinho.png',
        'Computer Service',
        'Company Fly',
        'Ferragem Construa',
        'Art Eletric Service',
        'Fly Home Desemploment',
    ]);
}
