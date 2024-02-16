<?php

namespace App\Http\Controllers;

use Intervention\Image\Facades\Image;

class FileController extends Controller
{

    public function getFile($type, $path)
    {
        abort_if(!in_array($type, ['file', 'image']), 404);

        $filePath = '';

        try {
            $decrypted = self::encryptDecrypt($path, 'decrypt');

            // The file name from $decrypted.
            $fileName = basename($decrypted);

            $file_data = file_get_contents(asset_url_local_s3($decrypted), false, stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]));

            if ($type == 'file') {
                // Stream the file to the browser.
                return response()->streamDownload(function () use ($file_data) {
                    echo $file_data;
                }, $fileName);
            }

            $filePath = Image::make($file_data)->response();
        } catch (\Exception $e) {
            abort(404);
        }

        return $filePath;
    }

    public static function encryptDecrypt($string, $action = 'encrypt')
    {

        $encryptMethod = 'AES-256-CBC';
        $secret_key = 'worksuite'; // User define private key
        $secret_iv = 'froiden'; // User define secret key
        $key = hash('sha256', $secret_key);
        $iv = substr(hash('sha256', $secret_iv), 0, 16); // sha256 is hash_hmac_algo

        if ($action == 'encrypt') {
            $output = openssl_encrypt($string, $encryptMethod, $key, 0, $iv);
            return base64_encode($output);
        }

        if ($action == 'decrypt') {
            return openssl_decrypt(base64_decode($string), $encryptMethod, $key, 0, $iv);
        }

    }

}
