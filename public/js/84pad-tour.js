/* 84PAD first-run guided tour. Local, dependency-free and privacy-preserving. */
(function () {
 "use strict";
 var page=document.body.dataset.tourPage;
 if (!["dashboard","settings"].includes(page) || (page==="dashboard" && document.body.dataset.tourOwner!=="1")) return;
 var guides={
  dashboard:[
   ["#tour-brand","Welcome to 84PAD","This is your Control Studio. The small Core runs your site; extra features are separate plugins."],
   ["#tour-overview","Your command centre","The Overview shows your site dashboard and status. You can explore without connecting cPanel."],
   ["#modules","Available systems","These are optional services. Cards marked future plugins will become functional when their plugins are installed and released."],
   ["#tour-ssl","SSL God","This planned plugin will manage certificates and renewals, using only the cPanel permissions that you allow."],
   ["#tour-email","Domain Email","The future Email plugin will manage mailboxes for your domain when you authorise it."],
   ["#tour-files","File Manager","The planned File Manager will help manage website files. It needs individual file-access permissions."],
   ["#tour-backups","Backups","The planned Backup plugin will schedule backups. Full automatic recovery is not yet included in this Core preview."],
   ["#plugins","Plugin manager","Discover, activate, deactivate and review installed plugins here."],
   ["#tour-plugin-upload","Signed plugin installation","Install trusted signed ZIP files up to 128 MB. New plugins stay disabled until you explicitly activate them."],
   ["#tour-security-link","Account security","Only the Owner can change Owner credentials and edit sensitive settings."],
   ["#tour-settings-link","Control your settings","Continue to settings to learn cPanel connection, feature toggles and individual plugin permissions."]
  ],
  settings:[
   ["#settings-intro","Core settings","Your website and unrelated plugins keep working even without a cPanel token."],
   ["#settings-cpanel","Connect cPanel once","Store a cPanel server, username and API token. The token is encrypted and hidden after saving."],
   ["#settings-features","On / Off controls","Independently control cPanel integration, plugin execution, signed uploads and UI motion."],
   ["#settings-permissions","Grant plugin access","Every installed plugin starts blocked. Allow only the email, SSL, files or backup capabilities you want."],
   ["#settings-tutorial","Replay training","You can reopen the guided tour whenever you want."],
   ["#settings-disconnect","Disconnect anytime","Remove saved cPanel credentials and revoke plugin API grants without deleting your website."]
  ]
 };
 var steps=guides[page].filter(function(s){return document.querySelector(s[0]);});
 if(!steps.length)return;
 var active=false,index=0,light,panel,heading,description,progress,feedback,back,next,triangle,previousFocus;
 function elem(tag,cls,txt){var e=document.createElement(tag);e.className=cls;if(txt)e.textContent=txt;return e;}
 function place(){
  if(!active)return;
  var e=document.querySelector(steps[index][0]);if(!e)return;
  var box=e.getBoundingClientRect(),vw=innerWidth,vh=innerHeight;
  light.style.left=Math.max(4,box.left-6)+"px";light.style.top=Math.max(4,box.top-6)+"px";
  light.style.width=Math.min(vw-8,box.width+12)+"px";light.style.height=Math.min(vh-8,box.height+12)+"px";
  var width=Math.min(355,vw-24),height=panel.offsetHeight||220;
  var below=box.bottom+20,above=box.top-height-20,goBelow=below+height<vh||above<12;
  panel.style.width=width+"px";
  panel.style.left=Math.max(12,Math.min(vw-width-12,box.left+box.width/2-width/2))+"px";
  panel.style.top=Math.max(12,Math.min(vh-height-12,goBelow?below:above))+"px";
  triangle.style.top=goBelow?"-9px":"auto";triangle.style.bottom=goBelow?"auto":"-9px";
  triangle.style.transform=goBelow?"rotate(45deg)":"rotate(225deg)";
 }
 function show(){
  var s=steps[index];
  heading.textContent=s[1];description.textContent=s[2];
  progress.textContent=(index+1)+" of "+steps.length+" · "+(page==="dashboard"?"Core tutorial":"Settings tutorial");
  next.textContent=index===steps.length-1?(page==="dashboard"?"Continue to Settings →":"Finish ✓"):"Next →";
  back.disabled=index===0;feedback.textContent="";
  document.querySelector(s[0]).scrollIntoView({block:"center",behavior:"instant"});
  requestAnimationFrame(function(){place();next.focus();});
 }
 async function complete(){
  var token=document.querySelector('meta[name="csrf-token"]');
  if(!token)return false;
  try{
   var response=await fetch(document.body.dataset.tourCompleteUrl,{method:"POST",credentials:"same-origin",
    headers:{"X-CSRF-TOKEN":token.content,"X-Requested-With":"XMLHttpRequest","Accept":"application/json"}});
   return response.ok;
  }catch(e){return false;}
 }
 function stop(){
  active=false;light.remove();panel.remove();
  window.removeEventListener("resize",place);window.removeEventListener("scroll",place,true);
  document.removeEventListener("keydown",keyHandler);
  if(previousFocus&&previousFocus.isConnected)previousFocus.focus();
 }
 async function finish(skip){
  if(!skip&&page==="dashboard"){
   stop();window.location.assign(document.body.dataset.tourSettingsUrl);return;
  }
  feedback.textContent="Saving your progress…";
  if(await complete())stop();
  else feedback.textContent="Could not save. Please retry.";
 }
 function keyHandler(e){
  if(e.key==="Escape"){e.preventDefault();void finish(true);}
  if(e.key==="ArrowRight"){e.preventDefault();next.click();}
  if(e.key==="ArrowLeft"&&index>0){e.preventDefault();back.click();}
 }
 function start(){
  if(active)return;active=true;index=0;previousFocus=document.activeElement;
  light=elem("div","pad-tour-light");light.setAttribute("aria-hidden","true");
  panel=elem("section","pad-tour-panel");panel.setAttribute("role","dialog");panel.setAttribute("aria-modal","true");
  panel.setAttribute("aria-label","84PAD guided tutorial");
  triangle=elem("div","pad-tour-arrow");progress=elem("div","pad-tour-progress");
  heading=elem("h2","pad-tour-title");description=elem("p","pad-tour-description");
  feedback=elem("p","pad-tour-feedback");feedback.setAttribute("role","status");
  var row=elem("div","pad-tour-buttons");
  var skip=elem("button","pad-tour-skip","Skip");skip.type="button";skip.addEventListener("click",function(){void finish(true);});
  back=elem("button","pad-tour-secondary","← Back");back.type="button";
  back.addEventListener("click",function(){if(index>0){index--;show();}});
  next=elem("button","pad-tour-primary","Next →");next.type="button";
  next.addEventListener("click",function(){if(index<steps.length-1){index++;show();}else void finish(false);});
  row.append(skip,back,next);panel.append(triangle,progress,heading,description,feedback,row);
  document.body.append(light,panel);
  if(!document.getElementById("pad-tour-css")){
   var css=elem("style","");css.id="pad-tour-css";
   css.textContent=".pad-tour-light{position:fixed;z-index:99980;pointer-events:none;border:3px solid #7ce8f6;border-radius:14px;box-shadow:0 0 0 300vmax rgba(3,8,18,.83),0 0 24px #7ce8f6;transition:all .18s}"+
    ".pad-tour-panel{position:fixed;z-index:99982;box-sizing:border-box;max-height:90vh;overflow:auto;background:#15243e;color:#eef7ff;border:1px solid #8bd5ef;border-radius:18px;box-shadow:0 24px 60px #000b;padding:20px;font-family:system-ui,sans-serif}"+
    ".pad-tour-arrow{position:absolute;left:32px;width:15px;height:15px;background:#15243e;border-top:1px solid #8bd5ef;border-left:1px solid #8bd5ef}"+
    ".pad-tour-progress{font-size:10px;letter-spacing:.14em;color:#86e9f2;font-weight:900;text-transform:uppercase}"+
    ".pad-tour-title{font-size:22px;margin:11px 0 8px;line-height:1.2}.pad-tour-description{font-size:13px;line-height:1.6;color:#c5d7eb;margin:0}"+
    ".pad-tour-feedback{font-size:11px;min-height:14px;margin:8px 0;color:#f8c9df}.pad-tour-buttons{display:flex;gap:7px;flex-wrap:wrap;align-items:center}"+
    ".pad-tour-buttons button{font:800 12px system-ui;border-radius:9px;padding:9px 11px;cursor:pointer;background:#273b5d;color:white;border:1px solid #7d98bd}"+
    ".pad-tour-buttons button:disabled{opacity:.3;cursor:default}.pad-tour-buttons .pad-tour-primary{background:#7feee6;color:#08252a;border:0}"+
    ".pad-tour-buttons .pad-tour-skip{margin-right:auto;border:0;background:transparent;color:#c7d6e8}"+
    ".pad-tour-buttons button:focus-visible{outline:2px solid #ffc5a4;outline-offset:2px}"+
    "@media(prefers-reduced-motion:reduce){.pad-tour-light{transition:none}}";
   document.head.append(css);
  }
  window.addEventListener("resize",place);window.addEventListener("scroll",place,true);
  document.addEventListener("keydown",keyHandler);show();
 }
 document.getElementById("tour-start-dashboard")?.addEventListener("click",start);
 document.getElementById("tour-start-settings")?.addEventListener("click",start);
 document.getElementById("tour-restart-settings")?.addEventListener("click",function(){
  window.location.assign(document.body.dataset.tourDashboardUrl||"/dashboard?tour=1");
 });
 if(document.body.dataset.tourAuto==="1")requestAnimationFrame(start);
})();