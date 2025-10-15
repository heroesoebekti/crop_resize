<?php
/**
 * Plugin Name: Crop and Resize Member Image
 * Plugin URI: https://github.com/heroesoebekti/
 * Description: Use for Crop and Resize member image
 * Version: 0.0.1
 * Author: Heru Subekti
 * Author URI: https://github.com/heroesoebekti/
 */

use SLiMS\DB;
use SLiMS\Filesystems\Storage;

$plugin = \SLiMS\Plugins::getInstance();

$dbs = DB::getInstance('mysqli');

$plugin->register('membership_init', function () use ($dbs) {
    if (isset($_GET['image_edit'])) {
        global $sysconf;
        include 'pop_image_edit.php';
        exit();
    }
});

$plugin->register('membership_custom_field_form', function ($form, &$js, $data) use ($dbs) {
    if (!isset($_GET['itemID']) || !$form->edit_mode) {
        return;
    }

    if (is_null($dbs)) {
        return;
    }

    $memberID = utility::filterData('itemID', 'get', true, true, true);
    $current_url_query = http_build_query($_GET);
    $pop_up_url = $_SERVER['PHP_SELF'] . '?' . $current_url_query . '&image_edit=true';

    $stmt = $dbs->prepare("SELECT member_image FROM member WHERE member_id = ?");

    if ($stmt) {
        $stmt->bind_param("s", $memberID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $memberImage = $row['member_image'];

            if (!empty($memberImage) && Storage::images()->isExists('persons/' . $memberImage)) {
                $cropButtonHtml = '<a href="' . $pop_up_url . '" id="cropImageButton" class="openPopUp notAJAX btn btn-secondary" title="Crop and Edit Image" style="display:none;" width="800" height="500"><i class="fa fa-crop" aria-hidden="true"></i> Crop / Resize</a>';

                $js .= "
                    var \$removeButton = $('.removeImage');
                    
                    if (\$removeButton.length > 0) {
                        \$removeButton.after('" . $cropButtonHtml . "');
                        
                        $('body').on('click', '.editFormLink', function() {
                            if (!\$removeButton.hasClass('makeHidden')) {
                                $('#cropImageButton').show();
                            }
                        });
                        
                        $('body').on('click', '.saveButton', function() {
                            $('#cropImageButton').hide();
                        });
                        
                        if (!\$removeButton.hasClass('makeHidden')) {
                            $('#cropImageButton').show();
                        }
                    }
                ";
            }
        }
        $stmt->close();
    }
});