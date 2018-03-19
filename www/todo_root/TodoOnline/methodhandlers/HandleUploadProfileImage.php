<?php
include_once('TodoOnline/base_sdk.php');

if($method == 'saveUploadedProfileImage')
{
    //We're going to copy the image in profile-images-tmp to profile-images
    $userId = $session->getUserId();
    $user = TDOUser::getUserForUserId($userId);
    
    if(empty($user))
    {
        error_log("saveUploadedProfileImage could not get session user");
        echo '{"success":false}';
        return;
    }
    
    $imageGuid = $user->imageGuid();
    if(empty($imageGuid))
    {
        error_log("saveUploadedProfileImage failed to find image guid for user");
        echo '{"success":false}';
        return;
    }
    
    $source1 = array();
    $source1['bucket'] = S3_IMAGE_UPLOAD_BUCKET;
    $source1['filename'] = "user-images/profile-images-tmp/$imageGuid";
    
    $source2 = array();
    $source2['bucket'] = S3_IMAGE_UPLOAD_BUCKET;
    $source2['filename'] = "user-images/profile-images-tmp-large/$imageGuid";
    
    $destination1 = array();
    $destination1['bucket'] = S3_IMAGE_UPLOAD_BUCKET;
    $destination1['filename'] = "user-images/profile-images/$imageGuid";
    
    $destination2 = array();
    $destination2['bucket'] = S3_IMAGE_UPLOAD_BUCKET;
    $destination2['filename'] = "user-images/profile-images-large/$imageGuid";
    
    $opts = array();
    $opts['acl'] = AmazonS3::ACL_PUBLIC;
    $opts['storage'] = AmazonS3::STORAGE_REDUCED;
   
    $s3 = new AmazonS3();
    $s3->path_style = TRUE;
    $response1 = $s3->copy_object($source1, $destination1, $opts);
    $response2 = $s3->copy_object($source2, $destination2, $opts);
    
    if($response1->isOK() && $response2->isOK())
    {
        //attempt to delete the old image, now that we have the new one saved
        $s3->delete_object(S3_IMAGE_UPLOAD_BUCKET, "user-images/profile-images-tmp/$imageGuid");
        $s3->delete_object(S3_IMAGE_UPLOAD_BUCKET, "user-images/profile-images-tmp-large/$imageGuid");
        
        //update the user's imageUpdateTimestamp
        $user->setImageUpdateTimestamp(time());
        $user->updateUser();
        
        echo '{"success":true, "imageguid":"'.$imageGuid.'"}';
    }
    else
    {
        error_log("s3->copy_object failed");
        echo '{"success":false}';
    }
    
}
elseif($method == 'removeProfileImage')
{
    $userId = $session->getUserId();
    $user = TDOUser::getUserForUserId($userId);
    
    if(empty($user))
    {
        error_log("removeProfileImage could not get session user");
        echo '{"success":false}';
        return;
    }
    
    $imageGuid = $user->imageGuid();
    if(empty($imageGuid))
    {
        error_log("removeProfileImage failed to find image guid for user");
        echo '{"success":false}';
        return;
    }
    
    $user->setImageGuid(NULL);
    $user->setImageUpdateTimestamp(0);
    
    if($user->updateUser())
    {
        $s3 = new AmazonS3();
        $s3->path_style = TRUE;
        $s3->delete_object(S3_IMAGE_UPLOAD_BUCKET, "user-images/profile-images-tmp/$imageGuid");
        $s3->delete_object(S3_IMAGE_UPLOAD_BUCKET, "user-images/profile-images/$imageGuid");
        
        echo '{"success":true}';
    }
    else
    {
        error_log("removeProfileImage failed to update user");
        echo '{"success":false}';
    }

}
elseif($method == 'uploadProfileImage')
{
    if(!isset($_FILES['picture']))
    {
        error_log("upload-picture called with no image set");
        echoPictureError("No image selected");
        return;
    }

    $file = $_FILES['picture'];

    if(isset($file['error']) && $file['error'] != 0)
    {
        switch($file['error'])
        {
            case UPLOAD_ERR_INI_SIZE:
            {
                error_log("INI size exceeded");
                echoPictureError("The file you have selected is too large (limit 2MB)");
                break;
            }
            case UPLOAD_ERR_FORM_SIZE:
            {
                error_log("Form size exceeded");
                echoPictureError("The file you have selected is too large (limit 2MB)");
                break;
            }
            case UPLOAD_ERR_PARTIAL:
            {
                error_log("Partial upload error");
                echoPictureError("Unable to complete upload");
                break;
            }
            case UPLOAD_ERR_NO_FILE:
            {
                error_log("No file chosen");
                echoPictureError("No file selected");
                break;
            }
            case UPLOAD_ERR_NO_TMP_DIR:
            {
                error_log("No tmp dir");
                echoPictureError("Upload failed");
                break;
            }
            case UPLOAD_ERR_CANT_WRITE:
            {
                error_log("Failed to write");
                echoPictureError("Upload failed");
                break;
            }
            case UPLOAD_ERR_EXTENSION:
            {
                error_log("Upload cancelled");
                echoPictureError("Upload cancelled");
                break;
            }
            default:
            {
                error_log("Unknown error with image file: ".$file["error"]);
                echoPictureError("Unknown error uploading image");
                break;
            }
        }
        return;
    }

    if(!isset($file['type']) || !isset($file['size']) || !isset($file['name']) || !isset($file['tmp_name']))
    {
        error_log("HandleImageUpload called with missing data");
        echoPictureError("Upload failed"); 
        return;  
    }

    $type = $file['type'];
    $size = $file['size'];
    $name = $file['name'];
    $tmpName = $file['tmp_name'];

    if($type != "image/gif" && $type != "image/jpeg" && $type != "image/pjpeg" && $type != "image/png")
    {
        error_log("HandleImageUpload called with bad file type");
        echoPictureError("Invalid image type (use jpg, gif, or png)");
        return; 
    }
    if($size > 3145728)//limit 3 megabytes
    {
        error_log("HandleImageUpload called with too large of a file");
        echoPictureError("The file you have selected is too large (limit 2MB)");
        return;
    }


    $user = TDOUser::getUserForUserId($session->getUserId());

    if($user->imageGuid() == NULL)
    {
        $user->setImageGuid(TDOUtil::uuid());
        $user->updateUser();
    }
        
    $imageGuid = $user->imageGuid();

    if(!TDOUtil::cropAndScaleImage($tmpName, $tmpName."large", PROFILE_IMAGE_SIZE * 2, PROFILE_IMAGE_SIZE * 2, $type) || !TDOUtil::cropAndScaleImage($tmpName, $tmpName."small", PROFILE_IMAGE_SIZE, PROFILE_IMAGE_SIZE, $type))
    {
        echoPictureError("Unable to crop image");
        return;         
    }
    

    $s3 = new AmazonS3();
    $s3->path_style = TRUE;
    $response1 = $s3->create_object(S3_IMAGE_UPLOAD_BUCKET, "user-images/profile-images-tmp-large/$imageGuid", array('fileUpload' => $tmpName."large",'acl' => AmazonS3::ACL_PUBLIC,'contentType' => 'image/*','storage' => AmazonS3::STORAGE_REDUCED));
    $response2 = $s3->create_object(S3_IMAGE_UPLOAD_BUCKET, "user-images/profile-images-tmp/$imageGuid", array('fileUpload' => $tmpName."small",'acl' => AmazonS3::ACL_PUBLIC,'contentType' => 'image/*','storage' => AmazonS3::STORAGE_REDUCED));

    if(!$response1->isOK() || !$response2->isOK())
    {
        echoPictureError("Unable to upload photo");
        return;        
    }
    else
    {
        echoPicturePreview(S3_BASE_TMP_USER_IMAGE_URL_LARGE.$imageGuid);
        return;
    }
    //    //remove the old image from s3 so it doesn't waste space
    //    if(!empty($oldImageGuid))
    //    {
    //        $deleteResponseFull = $s3->delete_object('static.plunkboard.com', "user-content/board-images/$oldImageGuid/full");
    //        $deleteResponseMed = $s3->delete_object('static.plunkboard.com', "user-content/board-images/$oldImageGuid/medium");
    //        $deleteResponseSmall = $s3->delete_object('static.plunkboard.com', "user-content/board-images/$oldImageGuid/small");
    //        if($deleteResponseFull->isOK() == false || $deleteResponseMed->isOK() == false || $deleteResponseSmall->isOK() == false)
    //        {
    //            error_log("Unable to delete old image on static.plunkboard.com");
    //        }
    //    }
    //   returnToPage($returnPage);
    //   return;
}


function echoPictureError($error_msg)
{
    echo '<script type="text/javascript">';
    echo 'var parDoc = window.parent.document;';
    echo "parDoc.getElementById('picture_error').innerHTML = '".$error_msg."';";
    echo "parDoc.getElementById('picture_preview').innerHTML = '';";
    echo '</script>';
}

function echoPicturePreview($srcUrl)
{
    echo '<script type="text/javascript">';
    echo 'var parDoc = window.parent.document;';
    
    echo 'parDoc.getElementById("picture_error").innerHTML =  "";';
    echo "parDoc.getElementById('picture_preview').innerHTML = '<img src=\'".$srcUrl."?lastmod=".time()."\' height=\"50\" width=\"50\" />';";
    echo "parDoc.getElementById('picture_save_button').style.display = 'inline-block';";
    echo "parDoc.getElementById('picture_save_button').setAttribute('class', 'button');";
    echo "parDoc.getElementById('picture_save_button').setAttribute('onclick', 'saveProfileImage()');";
    echo '</script>';
}


?>