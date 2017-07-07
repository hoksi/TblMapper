<?php

namespace hoksi;

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Layout
 *
 * @author Hoksi(hoksi3k@gmail.com)
 */
class Layout extends \CI_Controller {

    /**
     * Layout name
     * @var string
     */
    protected $layout;

    /**
     * Layout view path
     * @var string
     */
    protected $layoutPath;
    protected $jsFile;
    protected $cssFile;

    /**
     * Construct
     */
    public function __construct() {
        parent::__construct();

        $this->layout = 'default';
        $this->layoutPath = __DIR__ . '/views/layout';
        $this->jsFile = array();
        $this->cssFile = array();
    }

    /**
     * set Layout
     * 
     * @param string $layout
     * @return $this
     */
    public function setLayout($layout) {
        $this->layout = $layout;

        return $this;
    }

    /**
     * set Layout Path
     * 
     * @param string $layoutPath
     * @return $this
     */
    public function setLayoutPath($layoutPath) {
        $this->layoutPath = $layoutPath;

        return $this;
    }

    public function addJsFile($jsFile, $position_header = false) {
        $isTag = false;

        $jsf = strtolower(trim(str_replace("'", '"', $jsFile)));
        if (!strncmp($jsf, '<script', 7)) {
            $isTag = true;
            $tmp = explode('"', $jsf);
            $jsf = isset($tmp[1]) ? $tmp[1] : '';
        }

        if ($jsf) {
            $key = md5($jsf);
            $this->jsFile[$key] = array(
                'src' => ($isTag ? $jsFile : '<script src="' . $jsFile . '"></script>'),
                'isHeader' => $position_header
            );
        }

        return $this;
    }

    public function addCssFile($cssFile) {
        $isTag = false;

        $cssf = strtolower(trim(str_replace("'", '"', $cssFile)));
        if (!strncmp($jsf, '<link', 5)) {
            $isTag = true;
            $tmp = explode('"', $cssf);
            $cssf = isset($tmp[1]) ? $tmp[1] : '';
        }

        if ($cssf) {
            $key = md5($cssf);
            $this->cssFile[$key] = array(
                'src' => ($isTag ? $cssFile : '<link href="' . $cssFile . '"" rel="stylesheet">')
            );
        }

        return $this;
    }

    /**
     * Rendering Layout
     * 
     * @param string $view
     * @param array $data
     * @param boolean $return
     * @return type
     */
    public function view($view, $data = null, $return = false) {
        if ($data) {
            $this->load->vars($data);
        }

        if ($return) {
            return $this->load->file($this->layoutPath . '/' . $this->layout . '/header.php', true) .
                    $this->load->view($view, null, true) .
                    $this->load->file($this->layoutPath . '/' . $this->layout . '/footer.php', true);
        } else {
            $this->load->file($this->layoutPath . '/' . $this->layout . '/header.php');
            $this->load->view($view);
            $this->load->file($this->layoutPath . '/' . $this->layout . '/footer.php');
        }
    }

    public function _header() {
        $header = '';

        if (!empty($this->cssFile)) {
            foreach ($this->cssFile as $cssFile) {
                $header .= $cssFile['src'];
            }
        }

        if (!empty($this->jsFile)) {
            foreach ($this->jsFile as $jsFile) {
                if ($jsFile['isHeader']) {
                    $header .= $jsFile['src'];
                }
            }
        }

        return $header;
    }

    public function _footer() {
        $footer = '';

        if (!empty($this->jsFile)) {
            foreach ($this->jsFile as $jsFile) {
                if (!$jsFile['isHeader']) {
                    $footer .= $jsFile['src'];
                }
            }
        }
        
        return $footer;
    }

}

function get($key) {
    return get_instance()->load->get_var($key);
}

function header() {
    return get_instance()->_header();
}

function footer() {
    return get_instance()->_footer();
}
