var Slimbox;(function(){var I=0,G,O,B,W,Z,S,F,P,L=new Image(),M=new Image(),e,h,T,N,J,d,g,K,f,C,Y,X;window.addEvent("domready",function(){$(document.body).adopt($$([e=new Element("div",{id:"lbOverlay"}).addEvent("click",R),h=new Element("div",{id:"lbCenter"}),g=new Element("div",{id:"lbBottomContainer"})]).setStyle("display","none"));T=new Element("div",{id:"lbImage"}).injectInside(h).adopt(J=new Element("a",{id:"lbPrevLink",href:"#"}).addEvent("click",E),d=new Element("a",{id:"lbNextLink",href:"#"}).addEvent("click",V));N=new Element("iframe",{id:"lbImage"}).addEvent("load",A).injectInside(T);K=new Element("div",{id:"lbBottom"}).injectInside(g).adopt(new Element("a",{id:"lbCloseLink",href:"#"}).addEvent("click",R),Y=new Element("a",{id:"lbPrintLink",href:"#"}).addEvent("click",D),X=new Element("a",{id:"lbSaveLink",href:"#"}).addEvent("click",b),f=new Element("div",{id:"lbCaption"}),C=new Element("div",{id:"lbNumber"}),new Element("div",{styles:{clear:"both"}}));F={overlay:new Fx.Tween(e,{property:"opacity",duration:500}).set(0),image:new Fx.Tween(T,{property:"opacity",duration:500,onComplete:A}),bottom:new Fx.Tween(K,{property:"margin-top",duration:400})}});Slimbox={open:function(l,k,j){G=$extend({loop:false,overlayOpacity:0.8,resizeDuration:400,resizeTransition:false,initialWidth:250,initialHeight:250,psScriptPath:"",enablePrintButton:0,enableSaveButton:0,animateCaption:true,showCounter:true,counterText:"Item {x} of {y}",defaultIframeWidth:850,defaultIframeHeight:500},j||{});if(typeof l=="string"){l=[[l,k]];k=0}O=l;G.loop=G.loop&&(O.length>1);i();U(true);S=window.getScrollTop()+(window.getHeight()/15);F.resize=new Fx.Morph(h,$extend({duration:G.resizeDuration,onComplete:A},G.resizeTransition?{transition:G.resizeTransition}:{}));h.setStyles({top:S,width:G.initialWidth,height:G.initialHeight,marginLeft:-(G.initialWidth/2),display:""});F.overlay.start(G.overlayOpacity);I=1;return a(k)}};Element.implement({slimbox:function(j,k){$$(this).slimbox(j,k);return this}});Elements.implement({slimbox:function(j,m,l){m=m||function(n){return[n.href,n.title,n.rev]};l=l||function(){return true};var k=this;k.removeEvents("click").addEvent("click",function(){var n=k.filter(l,this);return Slimbox.open(n.map(m),n.indexOf(this),j)});return k}});function i(){e.setStyles({top:window.getScrollTop(),height:window.getHeight()})}function U(j){["object",window.ie?"select":"embed"].forEach(function(l){Array.forEach(document.getElementsByTagName(l),function(m){if(j){m._slimbox=m.style.visibility}m.style.visibility=j?"hidden":m._slimbox})});e.style.display=j?"":"none";var k=j?"addEvent":"removeEvent";window[k]("scroll",i)[k]("resize",i);document[k]("keydown",c)}function c(j){switch(j.code){case 27:case 88:case 67:R();break;case 37:case 80:E();break;case 39:case 78:V()}return false}function E(){return a(W)}function V(){return a(Z)}function a(j){if((I==1)&&(j>=0)){I=2;B=j;W=((B||!G.loop)?B:O.length)-1;Z=B+1;if(Z==O.length){Z=G.loop?0:-1}$$(J,d,T,N,g).setStyle("display","none");F.bottom.cancel().set(0);F.image.set(0);h.className="lbLoading";var k=O[B][0];var l=/\.(jpe?g|png|gif|bmp)/i;if(k.match(l)){$$(Y,X).setStyle("display","");P=new Image();P.datatype="image";P.onload=A;P.src=k}else{$$(Y,X).setStyle("display","none");P=new Object();P.datatype="iframe";rev=O[B][2];P.w=H(rev,new RegExp("width=(\\d+)","i"),G.defaultIframeWidth);P.h=H(rev,new RegExp("height=(\\d+)","i"),G.defaultIframeHeight);N.setProperties({id:"lbFrame_"+new Date().getTime(),width:P.w,height:P.h,scrolling:"yes",frameBorder:0,src:k})}}return false}function A(){switch(I++){case 2:h.className="";if(P.datatype=="image"){T.setStyles({backgroundImage:"url("+P.src+")",display:""});$$(T,K).setStyle("width",P.width);$$(T,J,d).setStyle("height",P.height);$$(J,d).setStyle("width","50%")}else{T.setStyles({backgroundImage:"",display:""});$$(T,K).setStyle("width",P.w);$$(T).setStyle("height",P.h);$$(J,d).setStyle("height","35px");$$(J,d).setStyle("width","65px");N.setStyles({display:""})}f.set("html",O[B][1]||"");C.set("html",(G.showCounter&&(O.length>1))?G.counterText.replace(/{x}/,B+1).replace(/{y}/,O.length):"");if(W>=0){L.src=O[W][0]}if(Z>=0){M.src=O[Z][0]}if(h.clientHeight!=T.offsetHeight){F.resize.start({height:T.offsetHeight});break}I++;case 3:if(h.clientWidth!=T.offsetWidth){F.resize.start({width:T.offsetWidth,marginLeft:-T.offsetWidth/2});break}I++;case 4:g.setStyles({top:S+h.clientHeight,marginLeft:h.style.marginLeft,visibility:"hidden",display:""});F.image.start(1);break;case 5:if(W>=0){J.style.display=""}if(Z>=0){d.style.display=""}if(G.animateCaption){F.bottom.set(-K.offsetHeight).start(0)}g.style.visibility="";I=1}}function R(){if(I){I=0;P.onload=$empty;for(var j in F){F[j].cancel()}$$(h,g).setStyle("display","none");F.overlay.chain(U).start(0)}return false}function H(m,k,l){var j=m.match(k);return j?j[1]:l}function D(){return Q("print")}function b(){return Q("save")}function Q(k){if(G.psScriptPath){var j=window.open(G.psScriptPath+"?mode="+k+"I="+O[B][0],"printsave","left=0,top=0,width="+(parseInt(T.style.width))+",height="+(parseInt(T.style.height))+",toolbar=0,resizable=1");return false}return true}})()