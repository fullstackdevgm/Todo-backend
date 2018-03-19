<?php
	
include_once('TodoOnline/base_sdk.php');

if($method == "uploadListImage")
{
    if(!isset($_POST['listid']))
    {
        error_log("HandleImageUpload missing param");
        alert("missing parameter");
        returnToPage(".");
        return;
    }

    $listid = $_POST['listid'];
    $returnPage = "?list=$listid&listsettings=kleenex";
}

if(!isset($_FILES['imageFile']))
{
    error_log("HandleImageUpload called with no image set");
    alert("No image set");
    returnToPage($returnPage);
    return;
}

$file = $_FILES['imageFile'];

if(isset($file['error']) && $file['error'] != 0)
{
    switch($file['error'])
    {
        case UPLOAD_ERR_INI_SIZE:
        {
            error_log("INI size exceeded");
            alert("The file you have selected is too large (limit 2MB)");
            break;
        }
        case UPLOAD_ERR_FORM_SIZE:
        {
            error_log("Form size exceeded");
            alert("The file you have selected is too large (limit 2MB)");
            break;
        }
        case UPLOAD_ERR_PARTIAL:
        {
            error_log("Partial upload error");
            alert("Unable to complete upload");
            break;
        }
        case UPLOAD_ERR_NO_FILE:
        {
            error_log("No file chosen");
            alert("No file selected");
            break;
        }
        case UPLOAD_ERR_NO_TMP_DIR:
        {
            error_log("No tmp dir");
            alert("Upload failed");
            break;
        }
        case UPLOAD_ERR_CANT_WRITE:
        {
            error_log("Failed to write");
            alert("Upload failed");
            break;
        }
        case UPLOAD_ERR_EXTENSION:
        {
            error_log("Upload cancelled");
            alert("Upload cancelled");
            break;
        }
        default:
        {
            error_log("Unknown error with image file: ".$file["error"]);
            alert("Error: ".$file["error"]);           
        }
    }
    returnToPage($returnPage);
    return;
}

if(!isset($file['type']) || !isset($file['size']) || !isset($file['name']) || !isset($file['tmp_name']))
{
    error_log("HandleImageUpload called with missing data");
    alert("Upload failed");
    returnToPage($returnPage);  
    return;  
}

$type = $file['type'];
$size = $file['size'];
$name = $file['name'];
$tmpName = $file['tmp_name'];

if($type != "image/gif" && $type != "image/jpeg" && $type != "image/pjpeg" && $type != "image/png")
{
    error_log("HandleImageUpload called with bad file type");
    alert("Invalid image type (use jpg, gif, or png)");
    returnToPage($returnPage); 
    return; 
}
if($size > 3145728)//limit 3 megabytes
{
    error_log("HandleImageUpload called with too large of a file");
    alert("The file you have selected is too large (limit 2MB)");
    returnToPage($returnPage);  
    return;
}

if($method == "uploadListImage")
{
    
    if(!TDOList::userCanEditList($listid, $session->getUserId()))
    {
        alert("You do not have permission to edit this list");
        returnToPage($returnPage);  
        return;  
    }

    
    $oldImageGuid = TDOList::getImageGuidForList($listid);
    $newImageGuid = TDOUtil::uuid();
    
    if(!TDOUtil::cropAndScaleImage($tmpName, $tmpName."medium", 136, 136, $type) || !TDOUtil::cropAndScaleImage($tmpName, $tmpName."small", 16, 16, $type))
    {
        alert("Unable to crop image");
        returnToPage($returnPage); 
        return;         
    }
    
    $s3 = new AmazonS3();
    $responseFull = $s3->create_object('static.plunkboard.com', "user-content/board-images/$newImageGuid/full", array('fileUpload' => $tmpName,'acl' => AmazonS3::ACL_PUBLIC,'contentType' => 'image/*','storage' => AmazonS3::STORAGE_REDUCED));
    $responseMed = $s3->create_object('static.plunkboard.com', "user-content/board-images/$newImageGuid/medium", array('fileUpload' => $tmpName."medium",'acl' => AmazonS3::ACL_PUBLIC,'contentType' => 'image/*','storage' => AmazonS3::STORAGE_REDUCED));
    $responseSmall = $s3->create_object('static.plunkboard.com', "user-content/board-images/$newImageGuid/small", array('fileUpload' => $tmpName."small",'acl' => AmazonS3::ACL_PUBLIC,'contentType' => 'image/*','storage' => AmazonS3::STORAGE_REDUCED));
    
    if(!$responseFull->isOK() || !$responseMed->isOK() || !$responseSmall->isOK())
    {
        alert("Unable to upload photo");
        returnToPage($returnPage);
        return;        
    }
    
    if(!TDOList::setImageGuidForList($listid, $newImageGuid))
    {
        //since we couldn't save, remove the new object from s3 so it doesn't waste space
        $deleteResponseFull = $s3->delete_object('static.plunkboard.com', "user-content/board-images/$newImageGuid/full");
        $deleteResponseMed = $s3->delete_object('static.plunkboard.com', "user-content/board-images/$newImageGuid/medium");
        $deleteResponseSmall = $s3->delete_object('static.plunkboard.com', "user-content/board-images/$newImageGuid/small");
        if($deleteResponseFull->isOK() == false || $deleteResponseMed->isOK() == false || $deleteResponseSmall->isOK() == false)
        {
            error_log("Unable to delete unsaved image on static.plunkboard.com");
        }
        alert("Unable to save image");
        returnToPage($returnPage);
        return;
    }   
    else
    {
        //remove the old image from s3 so it doesn't waste space
        if(!empty($oldImageGuid))
        {
            $deleteResponseFull = $s3->delete_object('static.plunkboard.com', "user-content/board-images/$oldImageGuid/full");
            $deleteResponseMed = $s3->delete_object('static.plunkboard.com', "user-content/board-images/$oldImageGuid/medium");
            $deleteResponseSmall = $s3->delete_object('static.plunkboard.com', "user-content/board-images/$oldImageGuid/small");
            if($deleteResponseFull->isOK() == false || $deleteResponseMed->isOK() == false || $deleteResponseSmall->isOK() == false)
            {
                error_log("Unable to delete old image on static.plunkboard.com");
            }
        }
       returnToPage($returnPage);
       return;
    }
}

function returnToPage($url)
{
    echo "<script type=\"text/javascript\">top.location=\"$url\"</script>";
}
function alert($msg)
{
    echo "<script type=\"text/javascript\">alert(\"$msg\");</script>";
}

?>