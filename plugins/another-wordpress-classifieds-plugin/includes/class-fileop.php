<?php
/**
 * Originally developed by Dan Caragea.
 * Permission is hereby granted to AWPCP to release this code
 * under the license terms of GPL2
 * @author Dan Caragea
 * http://datemill.com
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class fileop {

    public $op_mode = 'disk'; // 'disk' or 'ftp'

    public $ftp_id = null;

    private $wp_filesystem;

    public function __construct() {
        $this->wp_filesystem = awpcp_get_wp_filesystem();

        $ftp_error = false;
        if (defined('_FILEOP_MODE_') && _FILEOP_MODE_=='ftp' && defined('_FTPHOST_') && defined('_FTPUSER_') && defined('_FTPPASS_') && function_exists('ftp_connect')) {
            $this->ftp_id=ftp_connect(_FTPHOST_);
            if ($this->ftp_id) {
                if (@ftp_login($this->ftp_id,_FTPUSER_,_FTPPASS_)) {
                    ftp_pasv( $this->ftp_id,true);
                    $this->op_mode='ftp';
                } else {
                    $ftp_error=true;
                }
            }
        } else {
            $ftp_error=true;
        }
        if ($ftp_error) {
            $this->op_mode='disk';
            if ($this->ftp_id) {
                // invalid credentials
                ftp_quit($this->ftp_id);
            }
        }
    }

    // $file should have a full basepath (for 'disk' op mode). In case we're using ftp it will be converted to ftp path
    public function set_permission( $file, $mode ) {
        $myreturn='';
        if ($this->op_mode=='disk') {
            $myreturn = $this->wp_filesystem->chmod( $file, $mode );
        } elseif ($this->op_mode=='ftp') {
            $file   = str_replace( _BASEPATH_ . '/', _FTPPATH_, $file );
            //$old_de = defined( 'WP_DEBUG_DISPLAY' ) ? WP_DEBUG_DISPLAY : 0;
            //ini_set('display_errors',0);
            if (function_exists('ftp_chmod')) {
                $myreturn=@ftp_chmod($this->ftp_id,$mode,$file);
            } else {
                $myreturn=ftp_site($this->ftp_id,"CHMOD $mode $file");
            }
            //ini_set( 'display_errors', $old_de );
        }
    }

    // $source should have a full basepath (for 'disk' op mode)
    public function delete( $source ) {
        $myreturn=false;
        if ($this->op_mode=='disk') {
            $myreturn=$this->_disk_delete($source);
        } elseif ($this->op_mode=='ftp') {
            $is_dir = $this->wp_filesystem ? $this->wp_filesystem->is_dir($source) : is_dir($source);
            if ($is_dir && substr($source,-1)!='/') {
                $source.='/';
            }
            $source = str_replace( _BASEPATH_ . '/', _FTPPATH_, $source );
            //$old_de = defined( 'WP_DEBUG_DISPLAY' ) ? WP_DEBUG_DISPLAY : 0;
            //ini_set('display_errors',0);
            $myreturn=$this->_ftp_delete($source);
            //ini_set('display_errors',$old_de);
        }
        return $myreturn;
    }

    // internal function, do not call from outside. Call fileop->delete() instead
    // $source should have a full basepath
    protected function _disk_delete( $source ) {
        $myreturn = false;

        if ($this->wp_filesystem->is_dir($source)) {
            $files = $this->wp_filesystem->dirlist($source);
            if ( $files ) {
                foreach ( $files as $file => $fileinfo ) {
                    if ($file!='.' && $file!='..') {
                        $myreturn = $this->_disk_delete( $source . '/' . $file );
                    }
                }
            }
            $myreturn = $this->wp_filesystem->rmdir($source);
        } elseif ($this->wp_filesystem->is_file($source)) {
            $myreturn = $this->wp_filesystem->delete($source);
        }

        return $myreturn;
    }

    // internal function, do not call from outside. Call fileop->delete() instead
    // $source should have a full ftppath
    protected function _ftp_delete( $source ) {
        $myreturn=false;
        if (substr($source,-1)=='/') {
            @ftp_chdir($this->ftp_id,$source);
            $files = ftp_nlist( $this->ftp_id, '-aF .' ); // array or false on error. -F will append / to dirs
            if (empty($files)) {
                $temp = ftp_rawlist( $this->ftp_id, '-aF .' ); // array or false on error. -F will append / to dirs
                if (!empty($temp)) {
                    for ($i=0;isset($temp[$i]);++$i) {
                        $files[]=preg_replace('/.*:\d\d /','',$temp[$i]);
                    }
                }
            }
            if ($files!==false) {
                for ($i=0;isset($files[$i]);++$i) {
                    if ($files[$i]!='./' && $files[$i]!='../') {
                        $myreturn = $this->_ftp_delete( $source . '/' . $files[ $i ] );
                    }
                }
                $myreturn=@ftp_rmdir($this->ftp_id,$source);
            } else {
                $myreturn=false;// not enough.Should also break out of the recurring function in the for() above if $myreturn==false
            }
        } else {
            $myreturn=@ftp_delete($this->ftp_id,$source);
        }
        return $myreturn;
    }
}
