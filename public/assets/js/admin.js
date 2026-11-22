const adminMenuToggle=document.getElementById('adminMenuToggle');
const adminBackdrop=document.querySelector('.sidebar-backdrop');
function closeAdminSidebar(){
 document.body.classList.remove('sidebar-open');
 adminMenuToggle?.setAttribute('aria-expanded','false');
}
adminMenuToggle?.addEventListener('click',()=>{
 const isOpen=document.body.classList.toggle('sidebar-open');
 adminMenuToggle.setAttribute('aria-expanded',String(isOpen));
});
adminBackdrop?.addEventListener('click',closeAdminSidebar);
document.querySelectorAll('.admin-sidebar .sidebar-nav a').forEach(link=>link.addEventListener('click',closeAdminSidebar));

document.body.insertAdjacentHTML('beforeend',`
 <dialog class="admin-modal" id="adminEditModal" aria-labelledby="adminEditModalTitle">
  <header class="admin-modal-header"><div><small>Editar registo</small><h2 id="adminEditModalTitle">Editar</h2></div><button type="button" class="admin-modal-close" data-modal-close aria-label="Fechar"><i class="bi bi-x-lg"></i></button></header>
  <div class="admin-modal-content" id="adminEditModalContent"></div>
 </dialog>
 <dialog class="admin-modal admin-confirm-modal" id="adminConfirmModal" aria-labelledby="adminConfirmTitle">
  <header class="admin-modal-header"><div><small>Confirmação</small><h2 id="adminConfirmTitle">Confirmar acção</h2></div><button type="button" class="admin-modal-close" data-modal-close aria-label="Fechar"><i class="bi bi-x-lg"></i></button></header>
  <div class="admin-modal-content"><p class="admin-confirm-copy" id="adminConfirmMessage"></p></div>
  <footer class="admin-modal-actions"><button type="button" class="admin-modal-cancel" data-modal-close>Cancelar</button><button type="button" class="admin-modal-confirm" id="adminConfirmSubmit">Confirmar</button></footer>
 </dialog>
`);

const adminEditModal=document.getElementById('adminEditModal');
const adminConfirmModal=document.getElementById('adminConfirmModal');
let adminEditPlaceholder=null;
let adminEditOpener=null;
let adminPendingForm=null;

const confirmMessages={
 delete_project:'Tem a certeza de que pretende eliminar este projecto?',
 delete_service:'Tem a certeza de que pretende eliminar este serviço?',
 delete_testimonial:'Tem a certeza de que pretende eliminar este testemunho?',
 delete_comment:'Tem a certeza de que pretende eliminar este comentário?',
 delete_post:'Tem a certeza de que pretende eliminar este artigo e os comentários associados?'
};
document.querySelectorAll('form[onsubmit]').forEach(form=>{
 const action=form.querySelector('input[name="action"]')?.value;
 if(!confirmMessages[action])return;
 form.dataset.confirm=confirmMessages[action];
 form.removeAttribute('onsubmit');
});

document.addEventListener('click',event=>{
 const summary=event.target.closest('summary');
 if(summary&&adminEditModal){
  const details=summary.parentElement;
  const form=details?.querySelector('form');
  if(form){
   event.preventDefault();
   adminEditOpener=summary;
   adminEditPlaceholder=document.createComment('edit-form');
   form.before(adminEditPlaceholder);
   document.getElementById('adminEditModalContent').append(form);
   const row=details.closest('tr');
   document.getElementById('adminEditModalTitle').textContent=row?.querySelector('td b')?.textContent.trim()||'Editar registo';
   adminEditModal.showModal();
  }
 }

 const closeButton=event.target.closest('[data-modal-close]');
 if(closeButton)closeButton.closest('dialog')?.close();
 if(event.target===adminEditModal||event.target===adminConfirmModal)event.target.close();
});

adminEditModal?.addEventListener('close',()=>{
 if(adminEditPlaceholder?.isConnected){
  const form=document.getElementById('adminEditModalContent').querySelector('form');
  if(form)adminEditPlaceholder.replaceWith(form);
  else adminEditPlaceholder.remove();
 }
 adminEditPlaceholder=null;
 adminEditOpener?.focus();
 adminEditOpener=null;
});

document.addEventListener('submit',event=>{
 const form=event.target.closest('form[data-confirm]');
 if(!form||!adminConfirmModal)return;
 event.preventDefault();
 adminPendingForm=form;
 document.getElementById('adminConfirmMessage').textContent=form.dataset.confirm||'Confirma esta acção?';
 adminConfirmModal.showModal();
});

document.getElementById('adminConfirmSubmit')?.addEventListener('click',()=>{
 if(!adminPendingForm)return;
 const form=adminPendingForm;
 adminPendingForm=null;
 form.removeAttribute('data-confirm');
 adminConfirmModal.close();
 if(typeof form.requestSubmit==='function')form.requestSubmit();
 else form.submit();
});

adminConfirmModal?.addEventListener('close',()=>{adminPendingForm=null;});

const blogEditorForm=document.querySelector('[data-blog-editor]');
if(blogEditorForm){
 const editor=document.getElementById('blogContentEditor');
 const source=document.getElementById('blogContentSource');
 const toolbar=blogEditorForm.querySelector('.editor-toolbar');
 const editorError=document.getElementById('blogEditorError');
 let savedRange=null;
 editor.innerHTML=source.value;
 document.execCommand('defaultParagraphSeparator',false,'p');
 blogEditorForm.classList.add('editor-enabled');

 function saveEditorSelection(){
  const selection=window.getSelection();
  if(selection&&selection.rangeCount&&editor.contains(selection.anchorNode))savedRange=selection.getRangeAt(0).cloneRange();
 }
 function restoreEditorSelection(){
  editor.focus();
  if(!savedRange)return;
  const selection=window.getSelection();
  selection.removeAllRanges();
  selection.addRange(savedRange);
 }
 document.addEventListener('selectionchange',saveEditorSelection);
 editor.addEventListener('keyup',saveEditorSelection);
 editor.addEventListener('mouseup',saveEditorSelection);
 editor.addEventListener('input',()=>{editorError.hidden=true;saveEditorSelection();});
 toolbar.addEventListener('mousedown',event=>{
  if(event.target.closest('button'))event.preventDefault();
 });
 toolbar.addEventListener('click',event=>{
  const button=event.target.closest('[data-editor-command]');
  if(!button)return;
  restoreEditorSelection();
  document.execCommand(button.dataset.editorCommand,false,null);
  saveEditorSelection();
 });
 toolbar.querySelector('[data-editor-block]')?.addEventListener('change',event=>{
  restoreEditorSelection();
  document.execCommand('formatBlock',false,event.target.value);
  saveEditorSelection();
 });
 editor.addEventListener('paste',event=>{
  event.preventDefault();
  const text=event.clipboardData?.getData('text/plain')||'';
  document.execCommand('insertText',false,text);
  saveEditorSelection();
 });
 blogEditorForm.addEventListener('submit',event=>{
  source.value=editor.innerHTML;
  if(!editor.textContent.trim()){
   event.preventDefault();
   editorError.hidden=false;
   editor.focus();
  }
 });
}
