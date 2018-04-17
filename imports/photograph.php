<?php
    require_once(LIB_PATH.DS.'dbdml.php');      
    require_once(LIB_PATH.DS.'configuration.php');
    require_once(LIB_PATH.DS.'autoload.php');
    require_once(LIB_PATH.DS.'session.php');
    require_once(LIB_PATH.DS.'dboperation.php');
     
    class Photograph extends DatabaseMANI {

        protected static $table_name = "photos";
        protected static $db_fields = array('id', 'filename', 'type', 'size');
        public static $max_file_size = 1048576;
        public $id;
        public $filename;
        public $type;
        public $size;
        public $captured_file;
        private $tmp_path;
        protected $upload_directory = "uploaded_images";
        public $errors = array();

        protected $upload_error = array(
            UPLOAD_ERR_OK => "No errors",
            UPLOAD_ERR_INI_SIZE => "Larger than upload_max_filesize.",
            UPLOAD_ERR_FORM_SIZE => "Larger than form MAX_FILE_SIZE",
            UPLOAD_ERR_PARTIAL => "Partial upload",
            UPLOAD_ERR_NO_FILE => "No File",
            UPLOAD_ERR_NO_TMP_DIR => "No temporary directory",
            UPLOAD_ERR_CANT_WRITE => "Can't write to disk",
            UPLOAD_ERR_EXTENSION => "File upload stopped by extension"
        );

       public function attach_file($file) {
            $this->captured_file = $file;

            if(!$file || empty($file) || !is_array($file)) {
                $this->errors[] = "No file was uploaded.";
                $session->message("No file was uploaded.");
                redirect_to("../../index.php");

                return false;
            }else if($file['error'] != 0) {
                    $this->errors[] = $this->upload_error[$file['error']];
                    $session->message($this->upload_error[$file['error']]);
                    return false;
            }else {
                    $this->temp_path    = $file['tmp_name'];
                    $this->filename     = basename($file['name']);
                    $this->type         = $file['type'];
                    $this->size         = $file['size'];
                    return true;
            }
       } 

       public function save_image_to_db($file) {
           global $session;
           if(isset($this->id)) {
                $this->update();
           }else {
                if(!empty($this->errors)) {
                    return false;
                }

                if(empty($this->filename) || empty($this->temp_path)){
                    $this->errors[] = "The file location was not available.";
                    $session->message("The file location was not available.");
                    redirect_to("../../index.php");
                    return false;
                }

                $target_path = SITE_ROOT.DS.'public'.DS.'photo'.DS.$this->upload_directory.DS.$this->filename;

                if(file_exists($target_path)) {
                    $this->errors[] = "The file {$this->filename} already exists.";
                    $session->message("The file {$this->filename} already exists.");
                    redirect_to("../../index.php");
                    return false;
                }

                if(move_uploaded_file($this->temp_path, $target_path)) {
                    if($this->create()){
                        unset($this->temp_path);
                        return true;
                    }

                }else {
                    $this->errors[] = "The file upload failed, possibly due to incorrect permission on the upload folder";
                    $session->message("The file upload failed, possibly due to incorrect permission on the upload folder");
                    return false;
                }
            }
        }

        public function image_path(){
            return $this->upload_directory.DS.$this->filename;
        }

        public function delete_photo() {
            if($this->delete()) {
                $target_path = SITE_ROOT.DS.'public'.DS.'photo'.DS.$this->image_path();
                return unlink($target_path) ? true : false;
                redirect_to("../../index.php");
            }else {
                return false;
            }
        }

        public function load_image_to_display(){
            return 'public'.DS.'photo'.DS.$this->upload_directory.DS.$this->filename;
        }

        public function size_as_text() {
            if($this->size < 1024) {
                return "{$this->size} bytes";
            }else if($this->size < 1048576) {
                $size_kb = round($this->size/1024);
                return "{$size_kb} KB";
            }else {
                $size_mb = round($this->size/1048576, 1);
                return "{$size_mb} MB";
            }
        }
    }
?>