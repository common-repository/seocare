jQuery.noConflict();

(function($){
	$.fn.SeoCare = function(options){
    
    var defaults = {
   	    Rules: "",
        TitleField: "",
        TextField: "",
        Keywords: "",
        Tagwords: "",
        KeywordSeperator: ",",
        WordCount: "",
        OnTheFly: "false"
    };
    var options = $.extend(defaults, options);  
    var obj = $(this); 
	var arrGood = [], arrMedium = [], arrBad = [];
	var grade = 0, mediumTotalGrade = 0, lastBadLength = 0,lastMedLength = 0,lastGoodLength = 0, goodGrade = 0, mediumGrade = 0, badGrade = 0;
	var splitWordsText, splitWordsTitle, splitWordsPost; 
	var regex = new RegExp("[\.|\,|\?|\!|\:|\;]","g");
	
	//Array([Enabled],[Mandatory],[GoodMessage],[BadMessage],[Values],[Score]);
	var EnablePublish = options.Rules['EnablePublish'],
		TitleNotNull = options.Rules['TitleNotNull'],
		TitleLength = options.Rules['TitleLength'],
		TitleDisallowedWords = options.Rules['TitleDisallowedWords'],
		TextWordLength = options.Rules['TextWordLength'],
		KeywordsTitleMandatory = options.Rules['KeywordsTitleMandatory'],
		KeywordsDensity = options.Rules['KeywordsDensity'],
		TagsAppearance = options.Rules['TagsAppearance'];
		
	if(EnablePublish['enabled']){ $("#publishing-action").hide();}
	
	$("#ExecSeoCareCheck").click(function(){
		if(options.OnTheFly === 'true'){ 
			$(options.TitleField).unbind("change").change(function(){SeoCare();});
			$(options.TextField).unbind("change").change(function(){SeoCare();});
			$("#seocare_keywords").unbind("change").change(function(){SeoCare();});
		}
		SeoCare();
	});
	
	//Check everything the firsttime;
	//SeoCare();
	
	setInterval(function(){resultChanged();}, 5000); 
		
	function stripHtml(text)
	{
		text = text.replace(/(<([^>]+)>)/ig,"");
		text = text.replace("&nbsp;","");
		
		return text;
	}
	
	function SeoCare(){
		arrGood = [];
		arrMedium = [];
		arrBad = [];
		grade = 0;
		mediumTotalGrade = 0;

		var titleText = $(options.TitleField).val();
		var mainText = mainText = $('#content_ifr').contents().find('#tinymce').html();

		if(mainText == null || mainText == ""){
			$(options.TextField).val();
		}
		mainText = stripHtml(mainText);
				
		var tagText = $(options.Tagwords);
		
		splitWordsText = trimArr(mainText.toLowerCase().replace(regex,"").split(' '));
		splitWordsTitle = trimArr(titleText.toLowerCase().replace(regex,"").split(' '));

		splitWordsPost = splitWordsText;
		
		for (var x = 0; x <= splitWordsTitle.length; x++){	
			if(splitWordsTitle[x] !== undefined && splitWordsTitle[x].length > 0)
				splitWordsPost.push(splitWordsTitle[x]);
		}
				
		$("#SeoCareMessages").html("<img src='../wp-content/plugins/seocare/img/loader.gif'>&nbsp;Loading").show();
		Title(titleText,function(){
			Tags(tagText,function(){
				Keywords(function(){
					Text(mainText,function(){	
						
					});
				});
			});	
		});
	}
        
    function Text(strText,callback)
    {
    	if(TextWordLength['enabled'])
    	{
			var textLength = 0;
			if($(options.WordCount).html() != null && false){
				textLength = $(options.WordCount).html();
			}else{
				textLength = splitWordsText.length;
			}
					
	    	//Words in text
	    	if(textLength > TextWordLength['value']['min'] && textLength < TextWordLength['value']['max'])
	    	{addGood(TextWordLength);}else{ addBad(TextWordLength);	}
    	}
    	callback();
    }
    
    function Keywords(callback)
    {    	
    	var allKeywords = new Array();
    	allKeywords = trimArr($("#seocare_keywords").val().split(","));

    	if(allKeywords.length > 0)
    	{
    		var resultHtml = "";
    		var foundInTitle = false, goodDensity = true;
    		
    		for(var i = 0; i < allKeywords.length; i++)
    		{
    			if(allKeywords[i].length > 0 && allKeywords[i] != null)
    			{
    				var key = trim(allKeywords[i]);
    				var density = 0;
    				
    				for(var z = 0; z < splitWordsTitle.length; z++)
	        		{
	    				if(key.toLowerCase() == splitWordsTitle[z])
	    				{
	    					if(key.toLowerCase() == splitWordsTitle[z])
		    				{
		    					density++;
		    				}	    					
	    					foundInTitle = true;
	    				}
	        		}    			
	    			for(var y = 0; y < splitWordsText.length; y++)
	        		{
	    				if(key.toLowerCase() == splitWordsText[y])
	    				{
	    					density++;
	    				}
	        		}
	    			if(splitWordsText.length > 0 || splitWordsTitle.length > 0)
	    			{
	    				density = ((100/(splitWordsText.length + splitWordsTitle.length))*density);
	    			}
	    			
	    			var iconClass = "bad";
	    			if(density > KeywordsDensity['value']['min'] && density < KeywordsDensity['value']['max'])
	    			{
	    				iconClass = "good";
	    			}else{
	    				goodDensity = false;
	    			}	
	    			resultHtml += "<tr class=\"keywordResultRow\"><td><span class=\"icon "+iconClass+"\">"+ allKeywords[i] +"</span></td><td>"+ Math.ceil(density) +"%</td></tr>";
    			}else{
	    			if(allKeywords.length == 1)
	    				goodDensity = false;
    			}
    		}    		
    		if(KeywordsDensity['enabled'])
    		{
	    		if(goodDensity)
	    			addGood(KeywordsDensity);
	    		else
	    			addBad(KeywordsDensity);
    		}
    		
    		if(KeywordsTitleMandatory['enabled'])
    		{
	    		if(foundInTitle)
					addGood(KeywordsTitleMandatory);
				else
		    		addBad(KeywordsTitleMandatory);	
    		}
    		
    		$(".keywordResultRow").remove();
    		if(resultHtml.length > 0){
    			$("#seocare_Keywords_Result").show();
    			$("#seocare_Keywords_Result").after(resultHtml);
    		}else{
    			$("#seocare_Keywords_Result").hide();
    		}
    	}else{
    		addBad(KeywordsDensity);
    		addBad(KeywordsTitleMandatory);	
    	}
    	
    	callback();
    }
    
    function Tags(text,callback)
    {
    	var tagArr = new Array, i;
    	if(text.html() !== null)
    	{
	    	text.each(function(){
	    		tagArr.push($(this).text().substr(2));
	    		i++;
	    	});
	    	
	    	var nrGoodTagsAppear = 0;	    	
	    	if(tagArr !== undefined && tagArr.length > 0)
	    	{
				//Check for each tag
				for(var y = 0; y < tagArr.length; y++)
				{
					var nrTagsAppear = 0;
					for(var x = 0; x < splitWordsPost.length; x++)
					{
						if(splitWordsPost[x].toLowerCase().replace(regex,"") === tagArr[y].toLowerCase())
						{
							nrTagsAppear++;
						}
					}

					if(nrTagsAppear >= TagsAppearance['value']['nrtimes'])
					{
						nrGoodTagsAppear++;
					}
				}
	    	}
	    	if(nrGoodTagsAppear >= TagsAppearance['value']['nrtags'])
	    	{
	    		addGood(TagsAppearance);
	    	}else{
	    		addBad(TagsAppearance);
	    	}	    	
    	}else{
    		addBad(TagsAppearance);
    	}
    	
    	callback();
    }
    
    function Title(strTitle,callback)
    {	
    	if(TitleNotNull['enabled'])
    	{
    		if(strTitle.length == 0 || strTitle == null)
    		{
    			addBad(TitleNotNull);
    		}else
    		{
    			addGood(TitleNotNull);
    		}
    	}
    	
    	if(TitleLength['enabled'])
    	{
	    	//Length	
			if(splitWordsTitle.length >= TitleLength['value']['min'] && splitWordsTitle.length <= TitleLength['value']['max'])
			{
				addGood(TitleLength);
			}else{
				addBad(TitleLength);
			}
    	}

    	if(TitleDisallowedWords['enabled'])
    	{
			//Denied Words
			if(splitWordsTitle.length > 0)
			{
				var disallowed = TitleDisallowedWords['value'].split(",");
				
				var error = false;
				for(var i = 0; i < splitWordsTitle.length; i++)
				{
					for(var y = 0; y < disallowed.length; y++)
					{						
						if(splitWordsTitle[i] == disallowed[y])
						{
							addBad(TitleDisallowedWords);
							error = true;
							break;
						}
					}
					if(error === true){break;}
				}
				if(error === false){addGood(TitleDisallowedWords);}
			}
    	}
		callback();
    }
    
    function WriteResult()
	{
		var resultTable = "<table id=\"resultList\">";
		
		var scGrade = $("#SeoCareGrade");
		var gradeClass = "badGrade";
				
		//Check if the post is allowed to be published
		if(EnablePublish['enabled'])
		{
			if((grade+EnablePublish['grade']) >= 60){
				addGood(EnablePublish);
				$("#publishing-action").show();
			}else{
				addBad(EnablePublish);
				$("#publishing-action").hide();
			}
		}
		
		if(arrGood.length > 0)
		{
			//Write Good		
			for (var i = 0; i < arrGood.length; i++)
			{
				if(arrGood[i] !== undefined)
				{
					var medium = "";
					if(!arrGood[i]['mandatory']){medium = "medium";}
					resultTable +="<tr><td><span class=\"icon good\">"+arrGood[i]['goodmessage']+"</span></td><td class=\""+ medium +" grade\">"+ arrGood[i]['grade'].toFixed(1) +"%</td></tr>";
				}
			}
		}
		
		if(arrMedium.length > 0)
		{
			//Write Medium
			for (var i = 0; i < arrMedium.length; i++)
			{
				if(arrMedium[i] !== undefined)
				{
					resultTable += "<tr><td><span class=\"icon medium\">"+arrMedium[i]['badmessage']+"</span></td><td class=\"low grade\">"+arrMedium[i]['grade'].toFixed(1)+"%</td></tr>";
				}
			}
		}

		if(arrBad.length > 0)
		{
			//Write Bad
			for (var i = 0; i < arrBad.length; i++)
			{
				if(arrBad[i] !== undefined)
				{
					resultTable += "<tr><td class=\"message\"><span class=\"icon bad\">"+arrBad[i]['badmessage']+"</span></td><td class=\"low grade\">"+  arrBad[i]['grade'].toFixed(1)+"%</td></tr>";
				}
			}
		}
		
		if(grade >= 60)
		{
			if(mediumTotalGrade == 0)
				mediumTotalGrade = 100 - grade;
			grade += mediumTotalGrade;	
			
			scGrade.removeClass("badGrade").addClass("goodGrade");
		}else{
			scGrade.removeClass("goodGrade").addClass("badGrade");
		}
		grade = grade.toFixed(0);
		
		if(grade > 100){grade = 100;}
		scGrade.html(grade +"%");
		
		resultTable += "<tr><td></td><td></td></tr>";
		resultTable += "<tr><td></td><td class=\"Final grade\">"+ grade +"%</td></tr>";
		
		resultTable += "</table>";
		
		$("#SeoCareMessages").html(resultTable);
	}
	
	function resultChanged()
	{
		//Check if one of the message aray changed
		if(lastGoodLength != arrGood.length ||
		   lastMedLength != arrMedium.length ||
		   lastBadLength != arrBad.length)
		{
			WriteResult();
			lastBadLength = arrBad.length;
			lastMedLength = arrMedium.length;
			lastGoodLength = arrGood.length;
		}
	}
	
	function addGood(str)
	{ 
		arrGood[arrGood.length] = str;
		
		if(str['mandatory']){
			grade += str['grade'];
		}
		else{
			mediumTotalGrade += str['grade'];
		}
	}
	
	function addBad(str)
	{
		if(str['mandatory']){
			arrBad[arrBad.length] = str;
		}else{
			arrMedium[arrMedium.length] = str;
		}
	}        
 }
})(jQuery);

(function($){
	$.fn.SeoCareAdmin = function(options){
    
        var defaults = {};
        var options = $.extend(defaults, options);  
        var obj = $(this);
        
        $("#seocare_options_save").click(function(){$(":input", obj).attr('disabled', false);});
        
        $(".Enable",obj).each(function(){setInput(this);});
        $(".Enable",obj).click(function(){setInput(this);});        
        $(".Mandatory",obj).change(function(){getMandatory();});
        
        function setInput(thisObj){
        	if(!$(thisObj).attr('checked'))
        	{
        		$(":input",$(thisObj).parents("tr")).attr('disabled', true);
        		$(thisObj).attr('disabled', false);
        	}
        	else
        	{
        		$(":input",$(thisObj).parents("tr")).attr('disabled', false);
        	}        	
        	getEnabled();
        }
                
        function getMandatory(){
        	$("#seocare_nrmandatory").val($(".Mandatory:checked:enabled",obj).length);
        }
        
        function getEnabled(){
        	$("#seocare_nrenabled").val($(".Enable:checked:enabled",obj).length);
        	getMandatory();
        }
    }
})(jQuery);

//Removes leading whitespaces
function LTrim( value ) {
	var re = /\s*((\S+\s*)*)/;
	return value.replace(re, "$1");	
}

// Removes ending whitespaces
function RTrim( value ) {
	var re = /((\s*\S+)*)\s*/;
	return value.replace(re, "$1");	
}

// Removes leading and ending whitespaces
function trim( value ) {
	return LTrim(RTrim(value));	
}

function trimArr( value ) {
	if(value.length > 0)
	{
		var returnArr = new Array;
		
		for(var i = 0; i < value.length; i++)
		{
			returnVal = LTrim(RTrim(value[i]));
			
			if(returnVal !== undefined && returnVal.length > 0)
				returnArr.push(returnVal);
		}
		return returnArr;
	}else{
		return value;
	}
}


var ruleSet = "";

jQuery(document).ready( function($) {
	
	$("#seocareAdmin").SeoCareAdmin({});
		
	$("#seoCareResult").SeoCare({
		Rules: ruleSet,
		TitleField: "#title",
		TextField: "#content",
		Tagwords: "#post_tag .tagchecklist span",
		Keywords: "#seocare_checkKeywords",
		KeywordSeperator: ",",
		WordCount: "#word-count",
		OnTheFly: "true"
	});
	
});