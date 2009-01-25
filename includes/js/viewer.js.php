<?php
// Image Thumbnail Viewer Script- By Dynamic Drive, available at: http://www.dynamicdrive.com
// Last updated: Jan 22nd, 2007
?>
<script type="text/javascript">
var thumbnailviewer={
enableTitle: true, //Should "title" used as description?
enableAnimation: false, //Enable nimation?


 //Define HTML for footer interface
<?php 
	echo "definefooter: '<div class=\"footerbar\">" . get_bloginfo('wpurl') . " | close</div>',";
	echo "defineLoading: '<img src=\"" . get_bloginfo('wpurl') . "/wp-content/plugins/wp-classified/images/topic/loading.gif\" /> Loading Image...', "; 
?>
///No need to edit beyond here
scrollbarwidth: 16,
opacitystring: 'filter:progid:DXImageTransform.Microsoft.alpha(opacity=10); -moz-opacity: 0.1; opacity: 0.1',
targetlinks:[], //Array to hold links with rel="thumbnail"

createthumbBox:function(){
//write out HTML for Image Thumbnail Viewer plus loading div
document.write('<div id="thumbBox" onClick="thumbnailviewer.closeit()"><div id="thumbImage"></div>'+this.definefooter+'</div>')
document.write('<div id="thumbLoading">'+this.defineLoading+'</div>')
this.thumbBox=document.getElementById("thumbBox")
this.thumbImage=document.getElementById("thumbImage") //Reference div that holds the shown image
this.thumbLoading=document.getElementById("thumbLoading") //Reference "loading" div that will be shown while image is fetched
this.standardbody=(document.compatMode=="CSS1Compat")? document.documentElement : document.body //create reference to common "body" across doctypes
},

centerDiv:function(divobj){ //Centers a div element on the page
var ie=document.all && !window.opera
var dom=document.getElementById
var scroll_top=(ie)? this.standardbody.scrollTop : window.pageYOffset
var scroll_left=(ie)? this.standardbody.scrollLeft : window.pageXOffset
var docwidth=(ie)? this.standardbody.clientWidth : window.innerWidth-this.scrollbarwidth
var docheight=(ie)? this.standardbody.clientHeight: window.innerHeight
var docheightcomplete=(this.standardbody.offsetHeight>this.standardbody.scrollHeight)? this.standardbody.offsetHeight : this.standardbody.scrollHeight
var objwidth=divobj.offsetWidth 
var objheight=divobj.offsetHeight 
var topposition=(docheight>objheight)? scroll_top+docheight/2-objheight/2+"px" : scroll_top+10+"px" 
divobj.style.left=docwidth/2-objwidth/2+"px"
divobj.style.top=Math.floor(parseInt(topposition))+"px"
divobj.style.visibility="visible"
},

showthumbBox:function(){
this.centerDiv(this.thumbBox)
if (this.enableAnimation){
this.currentopacity=0.1
this.opacitytimer=setInterval("thumbnailviewer.opacityanimation()", 20)
}
},


loadimage:function(link){
if (this.thumbBox.style.visibility=="visible")
this.closeit()
var imageHTML='<img src="'+link.getAttribute("href")+'" style="'+this.opacitystring+'" />'
if (this.enableTitle && link.getAttribute("title"))
imageHTML+='<br />'+link.getAttribute("title")
this.centerDiv(this.thumbLoading)
this.thumbImage.innerHTML=imageHTML
this.featureImage=this.thumbImage.getElementsByTagName("img")[0]
this.featureImage.onload=function(){
thumbnailviewer.thumbLoading.style.visibility="hidden"
thumbnailviewer.showthumbBox() 
}
if (document.all && !window.createPopup)
this.featureImage.src=link.getAttribute("href")
this.featureImage.onerror=function(){ 
thumbnailviewer.thumbLoading.style.visibility="hidden"
}
},

setimgopacity:function(value){
var targetobject=this.featureImage
if (targetobject.filters && targetobject.filters[0]){ 
if (typeof targetobject.filters[0].opacity=="number") 
targetobject.filters[0].opacity=value*100
else 
targetobject.style.filter="alpha(opacity="+value*100+")"
}
else if (typeof targetobject.style.MozOpacity!="undefined") 
targetobject.style.MozOpacity=value
else if (typeof targetobject.style.opacity!="undefined") 
targetobject.style.opacity=value
else 
this.stopanimation()
},

opacityanimation:function(){ 
this.setimgopacity(this.currentopacity)
this.currentopacity+=0.1
if (this.currentopacity>1)
this.stopanimation()
},

stopanimation:function(){
if (typeof this.opacitytimer!="undefined")
clearInterval(this.opacitytimer)
},


closeit:function(){
this.stopanimation()
this.thumbBox.style.visibility="hidden"
this.thumbImage.innerHTML=""
this.thumbBox.style.left="-2000px"
this.thumbBox.style.top="-2000px"
},

cleanup:function(){
this.thumbLoading=null
if (this.featureImage) this.featureImage.onload=null
this.featureImage=null
this.thumbImage=null
for (var i=0; i<this.targetlinks.length; i++)
this.targetlinks[i].onclick=null
this.thumbBox=null
},

dotask:function(target, functionref, tasktype){
var tasktype=(window.addEventListener)? tasktype : "on"+tasktype
if (target.addEventListener)
target.addEventListener(tasktype, functionref, false)
else if (target.attachEvent)
target.attachEvent(tasktype, functionref)
},

init:function(){
if (!this.enableAnimation)
this.opacitystring=""
var pagelinks=document.getElementsByTagName("a")
for (var i=0; i<pagelinks.length; i++){
if (pagelinks[i].getAttribute("rel") && pagelinks[i].getAttribute("rel")=="thumbnail"){
pagelinks[i].onclick=function(){
thumbnailviewer.stopanimation()
thumbnailviewer.loadimage(this)
return false
}
this.targetlinks[this.targetlinks.length]=pagelinks[i]
}
}
this.dotask(window, function(){if (thumbnailviewer.thumbBox.style.visibility=="visible") thumbnailviewer.centerDiv(thumbnailviewer.thumbBox)}, "resize")


}
}

thumbnailviewer.createthumbBox() 
thumbnailviewer.dotask(window, function(){thumbnailviewer.init()}, "load")
thumbnailviewer.dotask(window, function(){thumbnailviewer.cleanup()}, "unload")
</script>