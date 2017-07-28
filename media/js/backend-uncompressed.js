/* backend-uncompressed.js 
2016-03-18: Funktion für Liste Ja/Nein (Farben) mit onchange=$.fn.changeColor
*/
;(function($){
 
 // GHSVS
 // for load(). Init
 $.fn.setListColor = function(listelement){
  $(".subform-repeatable-group select").each(function(){
   $.fn.changeListColor(this);
  })
 }
 // GHSVS
 $.fn.changeListColor = function(listelement){
  var seleb = $(listelement);
  // var chznb = $("#" + seleb.attr("id") + "_chzn");
  var color = parseInt(seleb.val()) == 1 ? "green":"red";
		// alert(seleb.val());
		seleb.parent("div").removeClass("STATUSred STATUSgreen").addClass("STATUS" + color);
		
  /*if (chznb.length){
   chznb.css({"border":"5px solid " + color});
  }else{
   seleb.css({"border":"5px solid " + color, "height": "40px"});
  }*/
 }

 $(window).on('load', function(){
  $.fn.setListColor();
 })
	$(document).on("subform-row-add", function(event, row)
	{
		$.fn.setListColor();
	});
})(jQuery);
