var lineHeight = 14;
var initialTextareaHeight = "13px";	
var currentKey = new String();
var lastKey = new String();

function shouldSubmitComment(event, submitTextarea, jsFunction, id, containerId) 
{
	//look ahead via clone textarea
	var parsedHeight  = new String();
	var cloneTextarea = document.getElementById('comments_clone_textarea');
	cloneTextarea.style.height = submitTextarea.style.height;
	cloneTextarea.value = "BU FFER " + submitTextarea.value;
	currentKey = event.keyCode;
	
	if(currentKey == "13" && lastKey=="16")
	{
		event.preventDefault();
		submitTextarea.value += "\n";
		
		//increase submitTextarea's height
		parsedHeight = cloneTextarea.style.height.replace("px", "");
		submitTextarea.style.height = parseInt(parsedHeight) + parseInt(lineHeight) + "px";
		cloneTextarea.style.height = submitTextarea.style.height;
		
		lastKey = "16";
		currentKey="";
	}
	else if(currentKey == "13" && lastKey != "16")
	{
		event.preventDefault();
		if (jsFunction != null && id != null && containerId != null)
			jsFunction(id, containerId);
		else if (jsFunction != null && id == null)
			jsFunction();
		else
			alert('');
        displayGlobalErrorMessage(labels.no_submit_method_was_assigned);
	} else
	{
		//increase submitTextarea's height if addition next few chars trigger a scroll
		if(cloneTextarea.clientHeight < cloneTextarea.scrollHeight)
		{
			parsedHeight = cloneTextarea.style.height.replace("px", "");
			submitTextarea.style.height = parseInt(parsedHeight) + parseInt(lineHeight) + "px";
			cloneTextarea.style.height = submitTextarea.style.height;
		}
		//decrease submitTextarea's height if deletion of next few chars trigger a scroll
		//...
		
		
		
		lastKey = currentKey;
	}
}

function htmlForComment(commentJSON)
{
	var comment = commentJSON;
	var imgurl = typeof(comment.imgurl) == 'undefined' ? 'https://fbcdn-profile-a.akamaihd.net/static-ak/rsrc.php/v1/yo/r/UlIqmHJn-SK.gif' : comment.imgurl;
                    	
   /*
 var commentId = commentJSON.commentid;
    var canRemove = false;
    if(commentJSON.canremove)
        canRemove = true;
        
    var name = commentJSON.username;
    
    var imgurl = "https://fbcdn-profile-a.akamaihd.net/static-ak/rsrc.php/v1/yo/r/UlIqmHJn-SK.gif";
    if(commentJSON.imgurl)
        imgurl = commentJSON.imgurl;
    var text = commentJSON.text;

    var readableDate = commentJSON.readabledate;
    var itemid = commentJSON.itemid;
    
*/

    /*
var html = '<li id="single_comment_' + commentId + '" class="single_comment">';
    
    //profile pic
    html += '<div class="comment_pic_container">';
    html += '<img class="small_profile_img comment_pic" src="' + imgurl + '" />';
    html += '</div>'; //end profile pic

    //comment body
    html += '<div class="comment_body">';
    html += ' <div>'; 
    html +=  '<a class="content_title" href="#">' + name + ' </a>';
    
    html += text;

    //echo ' '.html_entity_decode($text);

    html += ' </div>';//end text
    
    html += ' <div style="overflow:hidden;">';

    html += '<span>' + readableDate + '</span>';
    html += '</div>'; //end date

    html += '</div>'; //end comment_body
*/

	var html = '';
	 	html += '	<div class="container comment" id="task_comment_' + comment.commentid + '">';	
	    html += '		<img class="comment_author_pic" src="' + imgurl + '" title="' + comment.username + '"/>';
	    html += '		<div class="container comment_content">';
	    html += '			<span class="author">' + comment.username + ' </span>';
	    html += '			<span class="comment_text">' + replaceEmailAddressesWithHTMLLinks(replaceURLWithHTMLLinks(comment.text)) + '</span>';
	    html += '			<div class="timestamp">' + comment.readabledate + '</div>';
	    html += '		</div>';
	    html += '		<div class="delete_comment_button" onclick="removeTaskComment(\'' + comment.itemid + '\', \'' + comment.commentid+ '\')">✖</div>';
	    html += '	</div>';
	    
/*
    //delete button
    if(canRemove)
        html += '<img class="delete_button" src="https://s3.amazonaws.com/static.plunkboard.com/images/task/task-delete.png" onclick="removeTaskComment(\'' + itemid + '\', \'' + commentId + '\', \'' + itemid + '\')" />';

    //comment footer
    html += '<div class="comment_footer">';
    html += '</div>'; //end comment footer
    html += '</li>'; //end comment list item
    
*/
    return html;

}


function loadObjectComments(objectId, objectType, containerId)
{
	var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
	if(!ajaxRequest)
		return false;
	// Create a function that will receive data sent from the server
	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                //first make sure there wasn't an authentication error
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success && response.comments)
                {
                   // var html = '<input type="hidden" id="object_comment_count_' + containerId + '" value="' + response.comments.length + '" />';
                   	var html = '';
                    for(var i = 0; i < response.comments.length; i++)
                    {
                        html += htmlForComment(response.comments[i]);
                    }
					document.getElementById('comments_' + containerId).innerHTML = html;
                }
                else
                {
                    if(response.error)
                    {
                        if(response.error=="authentication")
                        {
                            //make the user log in again
                            history.go(0);
                            return;
                        }
                        
                        displayGlobalErrorMessage(response.error);
                    }
                    else
                    {
                        displayGlobalErrorMessage(labels.unable_to_load_tasks_comments);
                    }
                    
                }
                
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}
	
	params = "method=getCommentsForObject&itemid=" + objectId + "&itemtype=" + objectType;
	ajaxRequest.open("POST", ".", true);
	
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
}