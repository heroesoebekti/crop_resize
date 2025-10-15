<?php
/**
 * Plugin Name: Crop and Resize Member Image
 * Plugin URI: https://github.com/heroesoebekti/
 * Description: Use for Crop and Resize member image
 * Version: 1.0.0
 * Author: Heru Subekti
 * Author URI: https://github.com/heroesoebekti/
 */

declare(strict_types=1);

use SLiMS\DB;
use SLiMS\Url;
use SLiMS\Filesystems\Storage;

$plugin = \SLiMS\Plugins::getInstance();
$dbs = DB::getInstance('mysqli');

$plugin->register('membership_init', function () use ($dbs) {
    if (isset($_GET['image_edit'])) {
        global $sysconf;
        include __DIR__ . '/pop_image_edit.php';
        exit();
    }
});

$plugin->register('membership_custom_field_form', function ($form, string &$js, array $data) use ($dbs) {
    if (is_null($dbs)) {
        return;
    }

    $current_query_string = $_SERVER['QUERY_STRING'] ?? '';
    $pop_up_url = $_SERVER['PHP_SELF'] . '?' . $current_query_string . (empty($current_query_string) ? '' : '&') . 'image_edit=true';

    $memberID = filter_input(INPUT_GET, 'itemID', FILTER_VALIDATE_INT);
    
    if ($memberID !== false && $memberID !== null && $form->edit_mode) {
        
        $stmt = $dbs->prepare("SELECT member_image FROM member WHERE member_id = ?");
        
        if ($stmt) {
            $memberID_str = (string) $memberID;
            $stmt->bind_param("s", $memberID_str);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $memberImage = $row['member_image'] ?? null;
                
                if (!empty($memberImage) && Storage::images()->isExists('persons/' . $memberImage)) {
                    
                    $cropButtonHtml = '<a href="' . $pop_up_url . '" id="cropImageButton" class="openPopUp notAJAX btn btn-secondary" title="Crop and Edit Image" style="display:none;" width="800" height="500"><i class="fa fa-crop" aria-hidden="true"></i> Crop / Resize</a>';
                    
                    $js .= <<<JS
                        var \$removeButton = $('.removeImage');
                        if (\$removeButton.length > 0) {
                            \$removeButton.after('{$cropButtonHtml}'); 

                            $('body').on('click', '.editFormLink', function() {
                                if (!\$removeButton.hasClass('makeHidden')) { 
                                    $('#cropImageButton').show(); 
                                }
                            });

                            $('body').on('click', '.saveButton', function() {
                                $('#cropImageButton').hide();
                            });
                            
                            if (!\$removeButton.hasClass('makeHidden') && $('.editFormLink').hasClass('makeHidden')) {
                                $('#cropImageButton').show();
                            }
                        }
                    JS;
                }
            }
            $stmt->close();
        }
    }
});