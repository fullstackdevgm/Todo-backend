//*****
// calls to be executed when the file loads
//******

//getMoreListHistory();


//*****


var userLimit = 10;
var currentSearchString = null;
var currentUserResults = null;

function getUsersMatchingString(searchString)
{
    if(searchString == null || searchString.length == 0)
        return;

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
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success && response.users)
                {
                    var users = response.users;
                    var showMoreButton = (users.length == userLimit);
                    
                    if(currentSearchString == searchString)
                    {
                        currentUserResults.push.apply(currentUserResults, users);
                    }
                    else
                    {
                        currentUserResults = users;
                        currentSearchString = searchString;
                    }
                    
                    setResultHTML(showMoreButton);
                }
                else
                {
                    if(response.error == "authentication")
                    {
                        //make the user log in again
                        history.go(0);
                    }
                    else
                    {
                        alert("Failed to retrieve users");
                    }
                }
            }
            catch(e)
            {
                alert("Unknown response from server");
            }
        }
    }
    
    var offset = 0;
    if(currentSearchString == searchString)
    {
        offset = currentUserResults.length;
    }
    
    var params = "method=searchUsers&searchString=" + searchString + "&limit=" + userLimit + "&offset=" + offset;
    ajaxRequest.open("POST", "." , true);
    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
    ajaxRequest.send(params); 
}



function setResultHTML(showMoreButton)
{
    var htmlString = "";
    if(currentUserResults == null || currentUserResults.length == 0)
    {
        if(currentSearchString != null)
            htmlString = "No users found matching '" + currentSearchString + "'";
        else
            htmlString = "";
    }
    else
    {
    
        htmlString += '<table cellspacing="10"><tr><th>Username</th><th>First</th><th>Last</th></tr>';
    
        for(var i = 0; i < currentUserResults.length; i++)
        {
            var user = currentUserResults[i];
            htmlString += tableRowForUserJSON(user);
        }
        htmlString += '</table>';
        
        if(showMoreButton)
        {
            htmlString += '<a href="#" onclick="getUsersMatchingString(\'' + currentSearchString + '\')">Show More</a>';
        }
    }
    
    document.getElementById('user_search_results').innerHTML = htmlString;
}

function tableRowForUserJSON(user)
{
    var htmlString = '<tr>';
    
    htmlString += '<td>' + user.username + '</td>';
    htmlString += '<td>' + user.firstname + '</td>';
    htmlString += '<td>' + user.lastname + '</td>';
    
    htmlString += '<td><a href="#" onclick="showUserInfo(\'' + user.userid + '\')">View Details</a></td>';
    
    htmlString += '</tr>';
    return htmlString;
}


function showUserInfo(userid)
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
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success && response.user)
                {
                    displayUserInfoForUser(response.user);
                }
                else
                {
                    if(response.error == "authentication")
                    {
                        //make the user log in again
                        history.go(0);
                    }
                    else
                    {
                        if(response.error)
                            alert(response.error);
                        else
                            alert("Could not get information for user");
                    }
                }
            }
            catch(e)
            {
                alert("Could not get information for user " + e);
            }
        }
    }
    
    var params = "method=getUserInfo&userid=" + userid;
    ajaxRequest.open("POST", "." , true);
    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
    ajaxRequest.send(params); 

}

function displayUserInfoForUser(userJSON)
{
    var html = '<h1>' + userJSON.username + '</h1>';
    
    html += '<table>';
    html += '<tr><td>First Name: </td><td>' + userJSON.firstname + '</td></tr>';
    html += '<tr><td>Last Name: </td><td>' + userJSON.lastname + '</td></tr>';
    html += '<tr><td>Username: </td><td>' + userJSON.username + '</td></tr>';
    html += '<tr><td>User Id: </td><td>' + userJSON.userid + '</td></tr>';
    
    var fbString = "None";
    if(userJSON.oauth_uid)
        fbString = userJSON.oauth_uid;
    html += '<tr><td>Facebook Id: </td><td>' + fbString + '</td></tr>';
    
    var adminString = "None";
    if(userJSON.admin_level && userJSON.admin_level > 0)
    {
        if(userJSON.admin_level >= 10)
            adminString = "Support";
        if(userJSON.admin_level >= 20)
            adminString = "Developer";
        if(userJSON.admin_level >= 47)
            adminString = "Root";
    }
    html += '<tr><td>Admin Level: </td><td>' + adminString + '</td></tr>';
    
    html += '</table>';
    
    html += '<br><a href="#" onclick=hideUserInfo()><u>Back</u></a>';
    
    document.getElementById('user_data').style.display = "block";
    document.getElementById('user_data').innerHTML = html;
    
    document.getElementById('user_search_content').style.display = "none";
}

function hideUserInfo()
{
    document.getElementById('user_data').style.display = "none";
    document.getElementById('user_search_content').style.display = "block";
}






