<?php
/*
menggunakan mpdf versi 7
*/
include './mpdf/mpdf.php';

function calculateWidthHeight($widthPixels, $heightPixels)
{
    $a4WidthMM = 210;
    $a4HeightMM = $a4WidthMM * 1.4142; // Calculate height to maintain A4 aspect ratio

    // Calculate the width and height in millimeters maintaining the aspect ratio
    $widthMM = $a4WidthMM;
    if ($widthPixels > 0) {
      $heightMM = $heightPixels * $a4WidthMM / $widthPixels;

      return array($widthMM, $heightMM);
    }
}

function imgList( $folder_name ) {
  $dir = scandir($folder_name);
  return array_diff($dir, array('.', '..'));
}

function mpdfFlexibleHeight($dirName) {
  $imgFiles = imgList($dirName);
  natsort($imgFiles);

  $mpdf = new mPDF('utf-8');
  foreach ($imgFiles as $img) {
    $imgPath = "{$dirName}/{$img}";
    $imgSize = getimagesize( $imgPath );
    $imgWidthPx = $imgSize[0];
    $imgHeightPx = $imgSize[1];
    list($imgWidthMm, $imgHeightMm) = calculateWidthHeight($imgWidthPx, $imgHeightPx);
    $mpdf->AddPageByArray(array(
      'orientation' => 'P',
      'margin-top' => 0,
      'margin-bottom' => 0,
      'sheet-size' => array($imgWidthMm, $imgHeightMm)
    ));
    $mpdf->WriteHTML('');
    $mpdf->Image($imgPath,0,0,$imgWidthMm,$imgHeightMm,'jpg','',true, false);
  }
  $mpdf->Output("{$dirName}.pdf", "F");

}

mpdfFlexibleHeight('ch46');



?>
