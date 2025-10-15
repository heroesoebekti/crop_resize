<?php
 /*
 * @package Crop and Resize Member Image
 * @author Heru Subekti <https://github.com/heroesoebekti/>
 * @copyright 2025 Heru Subekti
 * @license GPL-3.0-or-later
 * @Created by          : Heru Subekti
 * @Date                : 2025-10-15 21:30:00
 * @File name           : pop_image_edit.php
 */

use SLiMS\Url;

require_once SB.'admin/default/session_check.inc.php';

if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token']['mainForm'])) {
    header('Content-Type: application/json');
    header('HTTP/1.0 403 Forbidden');
    die(json_encode(['success' => false, 'message' => __('Access denied: CSRF Token session (mainForm) not found.')]));
}

$mainFormTokens = $_SESSION['csrf_token']['mainForm'];
$latestTokenData = end($mainFormTokens);
$csrfToken = $latestTokenData['token'];

define('CROPPIE', (string)'../../../plugins/'.basename(__DIR__).DS);
$memberID = isset($_GET['itemID']) ? $_GET['itemID'] : null;
$memberImage = 'person.png';

if ($memberID) {
    $stmt = $dbs->prepare("SELECT member_image FROM member WHERE member_id = ?");
    if ($stmt) {
        $stmt->bind_param("s", $memberID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_row();
            if (!empty($row[0])) {
                $memberImage = $row[0];
            }
        }
        $stmt->close();
    }
}

$filenameToSave = $memberImage;

if (isset($_POST['image'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrfToken) {
        header('Content-Type: application/json');
        header('HTTP/1.0 403 Forbidden');
        echo json_encode(['success' => false, 'message' => __('Access denied: Invalid or missing CSRF Token.')]);
        exit;
    }

    $imageData = $_POST["image"];
    list($type, $imageData) = explode(';', $imageData);
    list(, $imageData) = explode(',', $imageData);
    $decodedData = base64_decode($imageData);
    
    $targetDir = IMGBS . 'persons/';
    $filePath = $targetDir . $filenameToSave;

    if (file_put_contents($filePath, $decodedData) !== FALSE) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => __('Image updated successfully.')]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => __('Failed to save file on the server.')]);
    }
    exit;
}

$urlEncodedMemberImage = urlencode($memberImage);
$htmlEscapedMemberImage = htmlspecialchars($memberImage, ENT_QUOTES, 'UTF-8');
$currentTimestamp = date('his');

ob_start();
?>
<div class="container">
    <div class="row justify-content-md-center">
        <div class="col-6">
            <img id="image_demo"
                 src="<?= SWB.'images/persons/'.$urlEncodedMemberImage.'?'.$currentTimestamp ?>"
                 style="width:100%; max-width:0px; display: block; margin: 0 auto; visibility: hidden;"/>
        </div>
        <div class="col-6">
            <div class="row pb-3">
                <div class="col-12 d-flex justify-content-center pt-3">
                    <img class="preview"
                          src=""
                          style="width:150px;height:200px;object-fit: cover;border: solid 4px #fff; border-radius: 12px; display: none;"/>
                </div>
            </div>
            <div class="row pt-2">
                <div class="col-12 d-flex justify-content-center">
                    <button type="button" class="btn btn-primary" id="crop_image"><?= __('Preview') ?></button>&nbsp;
                    <button type="button" class="btn btn-danger" id="update"><?= __('Update') ?></button>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="<?=CROPPIE?>js/croppie/croppie.js"></script>
<script>
    var $image_crop = $('#image_demo').croppie({
        enableExif: true,
        viewport: {
            width: 150,
            height: 200,
            type: 'square'
        },
        boundary: {
            width: 350,
            height: 400
        }
    });

    function loadDefaultPreview() {
        const defaultImgSrc = '<?= SWB.'images/persons/'.$urlEncodedMemberImage.'?'.$currentTimestamp ?>';
        if ('<?=$htmlEscapedMemberImage?>' !== 'person.png') {
            $('.preview').attr('src', defaultImgSrc).show();
        }
    }
    loadDefaultPreview();

    $('#crop_image').on('click', function(){
        $image_crop.croppie('result', {
            type: 'canvas',
            size: 'viewport'
        }).then(function(response){
            $('.preview').attr('src', response).show();
        });
    });

    $('#update').on('click', function(){
        $image_crop.croppie('result', {
            type: 'canvas',
            size: 'viewport',
            format: 'jpeg',
            quality: 0.9
        }).then(function(response){
            $.ajax({
                url: window.location.href,
                type: "POST",
                data: {
                    "image": response,
                    "filename": "<?=$htmlEscapedMemberImage?>",
                    "csrf_token": "<?=$csrfToken?>"
                },
                dataType: "json",
                beforeSend: function() {
                    $('#update').attr('disabled', true).text('<?= __('Processing...') ?>');
                },
                success: function(res) {
                    if(res.success) {
                        alert(res.message);
                        parent.$('#mainContent').simbioAJAX('<?=MWB?>membership/index.php');
                    } else {
                        alert('<?= __('Update failed: ') ?>' + res.message);
                    }
                },
                error: function(xhr, status, error) {
                     alert('<?= __('An AJAX error occurred. Please ensure you have a valid login session.') ?>');
                },
                complete: function() {
                    $('#update').attr('disabled', false).text('<?= __('Update') ?>');
                }
            });
        });
    });
</script>
<?php
$content = ob_get_clean();
$js = '<script type="text/javascript" src="'.JWB.'jquery.js"></script>
    <script type="text/javascript" src="'.JWB.'colorbox/jquery.colorbox-min.js"></script>
    <script type="text/javascript" src="'.JWB.'gui.js"></script>'."\n";
$css = '<link rel="stylesheet" type="text/css" href="'.CROPPIE.'js/croppie/croppie.css"/>'."\n";

require SB.'/admin/'.$sysconf['admin_template']['dir'].'/printed_page_tpl.php';
