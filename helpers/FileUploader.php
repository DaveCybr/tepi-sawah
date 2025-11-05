<?php

/**
 * File Upload Helper Class
 * Secure file upload dengan validasi lengkap
 */

class FileUploader
{
    /**
     * Upload image dengan validasi keamanan
     * 
     * @param array $file File dari $_FILES
     * @param string $uploadDir Target directory
     * @return string Filename yang diupload
     * @throws Exception Jika upload gagal
     */
    public static function uploadImage($file, $uploadDir = null)
    {
        if ($uploadDir === null) {
            $uploadDir = UPLOAD_PATH;
        }

        // Validasi error upload
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception('Parameter file tidak valid');
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new Exception('Tidak ada file yang diupload');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('File terlalu besar. Maksimal ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB');
            default:
                throw new Exception('Upload error: ' . $file['error']);
        }

        // Validasi ukuran file
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception('File terlalu besar. Maksimal ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB');
        }

        // Validasi ekstensi file
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExt, ALLOWED_IMAGE_TYPES)) {
            throw new Exception('Tipe file tidak diizinkan. Hanya: ' . implode(', ', ALLOWED_IMAGE_TYPES));
        }

        // Validasi MIME type (security critical)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw new Exception('Tidak dapat memvalidasi tipe file');
        }

        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
            throw new Exception('File tidak valid. Hanya gambar yang diperbolehkan');
        }

        // Validasi ukuran gambar (cek apakah benar-benar gambar)
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new Exception('File bukan gambar yang valid');
        }

        // Pastikan directory exists
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception('Gagal membuat direktori upload');
            }
        }

        // Generate unique filename (security: prevent file overwrite)
        $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $originalName);
        $newFilename = uniqid() . '_' . time() . '_' . substr($safeName, 0, 50) . '.' . $fileExt;
        $destination = $uploadDir . $newFilename;

        // Move file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Gagal mengupload file');
        }

        // Set permissions
        chmod($destination, 0644);

        return $newFilename;
    }

    /**
     * Delete file dengan validasi
     * 
     * @param string $filename Nama file
     * @param string $uploadDir Directory file
     * @return bool
     */
    public static function deleteFile($filename, $uploadDir = null)
    {
        if ($uploadDir === null) {
            $uploadDir = UPLOAD_PATH;
        }

        if (empty($filename)) {
            return false;
        }

        // Security: prevent directory traversal
        $filename = basename($filename);
        $filepath = $uploadDir . $filename;

        if (file_exists($filepath) && is_file($filepath)) {
            return @unlink($filepath);
        }

        return false;
    }

    /**
     * Get file URL
     * 
     * @param string $filename
     * @return string
     */
    public static function getFileUrl($filename)
    {
        if (empty($filename)) {
            return APP_URL . '/assets/images/no-image.png';
        }

        return APP_URL . '/assets/uploads/' . basename($filename);
    }

    /**
     * Validate uploaded file sebelum processing
     * 
     * @param array $file
     * @return bool
     */
    public static function validateUpload($file)
    {
        try {
            if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                return false;
            }

            if ($file['size'] > MAX_FILE_SIZE) {
                return false;
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            return in_array($mimeType, ALLOWED_MIME_TYPES);
        } catch (Exception $e) {
            return false;
        }
    }
}
