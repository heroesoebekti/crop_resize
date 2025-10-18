<?php
/**
 * Plugin Name: Crop and Resize Image
 * Plugin URI: https://github.com/heroesoebekti/crop_resize
 * Description: Use for Crop and Resize image
 * Version: 0.0.1
 * Author: Heru Subekti
 * Author URI: https://github.com/heroesoebekti/
 */

use SLiMS\DB;
use SLiMS\Filesystems\Storage;

if (!defined('ITEM_ID_GET_NAME')) define('ITEM_ID_GET_NAME', 'itemID');

$plugin = \SLiMS\Plugins::getInstance();

global $sysconf;

if (!defined('PDO::FETCH_ASSOC')) {
    if (class_exists('PDO')) {
    }
}

$dbs = DB::getInstance();

function injectCropButtonAndJS(string $imagePath, string $pop_up_url, string &$js): void
{
    if (!empty($imagePath) && Storage::images()->isExists($imagePath)) {
        $cropButtonHtml = '<a href="' . $pop_up_url . '" id="cropImageButton" class="openPopUp notAJAX btn btn-secondary" title="Crop and Edit Image" style="display:none;" width="800" height="500"><i class="fa fa-crop" aria-hidden="true"></i>&nbsp;'.__('EDIT').'</a>';
        $js .= "
            var \$removeButton = $('.removeImage');
            if (\$removeButton.length > 0) {
                \$removeButton.after('" . $cropButtonHtml . "');
                if (!\$removeButton.hasClass('makeHidden')) {
                    $('#cropImageButton').show();
                }
                $('body').on('click', '.editFormLink', function() {
                    if (!\$removeButton.hasClass('makeHidden')) {
                        $('#cropImageButton').show();
                    }
                });
                $('body').on('click', '.saveButton', function() {
                    $('#cropImageButton').hide();
                });
            }
        ";
    }
}

function openPopup() {
    if (isset($_GET['edit'])) {
        global $sysconf, $dbs;
        include 'pop_image_edit.php';
        exit();
    }
}

$plugin->register('membership_init', function () {
    openPopup();
});

$plugin->register('bibliography_init', function () {
    openPopup();
});

$plugin->register('advance_custom_field_form', function ($form, &$js, $data) use ($dbs) {
    if (!isset($_GET[ITEM_ID_GET_NAME]) || !$form->edit_mode || is_null($dbs)) {
        return;
    }
    $biblioID = utility::filterData(ITEM_ID_GET_NAME, 'get', true, true, true);    
    if (empty($biblioID)) return;
    $current_url_query = http_build_query($_GET);
    $pop_up_url = $_SERVER['PHP_SELF'] . '?' . $current_url_query . '&image=biblio&edit=true';
    $stmt = $dbs->prepare("SELECT image FROM biblio WHERE biblio_id = ?");
    if ($stmt) {
        $stmt->execute([$biblioID]);         
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row) {
            $biblioImage = $row['image'];
            injectCropButtonAndJS('docs/' . $biblioImage, $pop_up_url, $js);
        }
    }
});

$plugin->register('membership_custom_field_form', function ($form, &$js, $data) use ($dbs) {
    if (!isset($_GET[ITEM_ID_GET_NAME]) || !$form->edit_mode || is_null($dbs)) {
        return;
    }    
    $memberID = utility::filterData(ITEM_ID_GET_NAME, 'get', true, true, true);
    if (empty($memberID)) return;
    $current_url_query = http_build_query($_GET);
    $pop_up_url = $_SERVER['PHP_SELF'] . '?' . $current_url_query . '&image=member&edit=true';
    $stmt = $dbs->prepare("SELECT member_image FROM member WHERE member_id = ?");
    if ($stmt) {
        $stmt->execute([$memberID]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row) {
            $memberImage = $row['member_image'];
            injectCropButtonAndJS('persons/' . $memberImage, $pop_up_url, $js);
        }
    }
});