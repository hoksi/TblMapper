<?php

namespace Hoksi;

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Toast
 * 
 * JUnit-style unit testing in CodeIgniter. Requires PHP 5 (AFAIK). Subclass
 * this class to create your own tests. See the README file or go to
 * http://jensroland.com/projects/toast/ for usage and examples.
 * 
 * RESERVED TEST FUNCTION NAMES: test_index, test_show_results, test__[*]
 * 
 * @package     CodeIgniter
 * @subpackage  Controllers
 * @category    Unit Testing
 * @based       on Brilliant original code by user t'mo from the CI forums
 * @based       on Assert functions by user 'redguy' from the CI forums
 * @license     Creative Commons Attribution 3.0 (cc) 2009 Jens Roland
 * @author      Jens Roland (mail@jensroland.com)
 * 
 */
/**
 * 
 */

/**
 * Codeignter Test Framework Class
 */
abstract class Toast extends \CI_Controller {

    // The folder INSIDE /controllers/ where the test classes are located
    // TODO: autoset
    public $modelname;
    public $modelname_short;
    public $message;
    public $messages;
    public $asserts;
    public $whiteList;
    public $blackList;

    /**
     * Construct
     * @param string $name
     */
    public function __construct() {
        parent::__construct();

        $this->load->library('unit_test');

        $this->modelname = $this->router->class;
        $this->modelname_short = $this->router->class;
        $this->messages = array();
        $this->whiteList = array();
        $this->blackList = array();
    }

    /**
     * Run All Tests
     */
    public function index() {
        $this->_show_all();
    }

    /**
     * 
     */
    public function show_results() {
        $this->_run_all();
        $data['modelname'] = $this->modelname;
        $data['results'] = $this->unit->result();
        $data['messages'] = $this->messages;
        $this->_view_results($data);
    }

    protected function _show_all() {
        $this->_run_all();
        $data['modelname'] = $this->modelname;
        $data['results'] = $this->unit->result();
        $data['messages'] = $this->messages;

        $this->_view_header();
        $this->_view_results($data);
        $this->_view_footer();
    }

    protected function _show($method) {
        $this->_run($method);
        $data['modelname'] = $this->modelname;
        $data['results'] = $this->unit->result();
        $data['messages'] = $this->messages;

        $this->_view_header();
        $this->_view_results($data);
        $this->_view_footer();
    }

    protected function _run_all() {
        foreach ($this->_get_test_methods() as $method) {
            $this->_run($method);
        }
    }

    protected function _run($method) {
        // Reset message from test
        $this->message = '';

        // Reset asserts
        $this->asserts = TRUE;

        // Run cleanup method _pre
        $this->_pre();

        // Run test case (result will be in $this->asserts)
        $this->{$method}();

        // Run cleanup method _post
        $this->_post();

        // Set test description to "model name -> method name" with links
        $this->load->helper('url');

        $test_class_segments = $this->router->directory . strtolower($this->modelname_short);
        $test_method_segments = $test_class_segments . '/' . substr($method, 4);
        $desc = anchor($test_class_segments, $this->modelname_short) . ' -> ' . anchor($test_method_segments, substr($method, 4));

        $this->messages[] = $this->message;

        // Pass the test case to CodeIgniter
        $this->unit->run($this->asserts, TRUE, $desc);
    }

    protected function _get_test_methods() {
        $methods = get_class_methods($this);
        $testMethods = array();

        foreach ($methods as $method) {
            if (!strncmp(strtolower($method), 'test', 4)) {
                if (
                        (!empty($this->blackList) && in_array($method, $this->blackList)) ||
                        (!empty($this->whiteList) && !in_array($method, $this->whiteList))
                ) {
                    continue;
                }
                
                $testMethods[] = $method;
            }
        }

        return $testMethods;
    }

    /**
     * Remap function (CI magic function)
     * 
     * Reroutes any request that matches a test function in the subclass
     * to the _show() function.
     * 
     * This makes it possible to request /my_test_class/my_test_function
     * to test just that single function, and /my_test_class to test all the
     * functions in the class.
     * 
     */
    public function _remap($method) {
        $test_name = 'test' . $method;
        if (method_exists($this, $test_name)) {
            $this->_show($test_name);
        } else {
            $this->{$method}();
        }
    }

    /**
     * Cleanup function that is run before each test case
     * Override this method in test classes!
     */
    protected function _pre() {
        
    }

    /**
     * Cleanup function that is run after each test case
     * Override this method in test classes!
     */
    protected function _post() {
        
    }

    public function fail($message = null) {
        return $this->_fail($message);
    }

    protected function _fail($message = null) {
        $this->asserts = FALSE;
        if ($message != null) {
            $this->message = $message;
        }
        return FALSE;
    }

    public function assertTrue($assertion) {
        return $this->_assert_true($assertion);
    }

    protected function _assert_true($assertion) {
        if ($assertion) {
            return TRUE;
        } else {
            $this->asserts = FALSE;
            return FALSE;
        }
    }

    public function assertFalse($assertion) {
        $this->_assert_false($assertion);
    }

    protected function _assert_false($assertion) {
        if ($assertion) {
            $this->asserts = FALSE;
            return FALSE;
        } else {
            return TRUE;
        }
    }

    public function assertTrueStrict($assertion) {
        $this->_assert_true_strict($assertion);
    }

    protected function _assert_true_strict($assertion) {
        if ($assertion === TRUE) {
            return TRUE;
        } else {
            $this->asserts = FALSE;
            return FALSE;
        }
    }

    public function assertFalseStrict($assertion) {
        $this->_assert_false_strict($assertion);
    }

    protected function _assert_false_strict($assertion) {
        if ($assertion === FALSE) {
            return TRUE;
        } else {
            $this->asserts = FALSE;
            return FALSE;
        }
    }

    public function assertEquals($base, $check) {
        return $this->_assert_equals($base, $check);
    }

    protected function _assert_equals($base, $check) {
        if ($base == $check) {
            return TRUE;
        } else {
            $this->asserts = FALSE;
            return FALSE;
        }
    }

    public function assertNotEquals($base, $check) {
        return $this->_assert_not_equals($base, $check);
    }

    protected function _assert_not_equals($base, $check) {
        if ($base != $check) {
            return TRUE;
        } else {
            $this->asserts = FALSE;
            return FALSE;
        }
    }

    public function assertEqualsStrict($base, $check) {
        return $this->_assert_equals_strict($base, $check);
    }

    protected function _assert_equals_strict($base, $check) {
        if ($base === $check) {
            return TRUE;
        } else {
            $this->asserts = FALSE;
            return FALSE;
        }
    }

    public function assertNotEquals_strict($base, $check) {
        return $this->_assert_not_equals_strict($base, $check);
    }

    protected function _assert_not_equals_strict($base, $check) {
        if ($base !== $check) {
            return TRUE;
        } else {
            $this->asserts = FALSE;
            return FALSE;
        }
    }

    public function assertEmpty($assertion) {
        return $this->_assert_empty($assertion);
    }

    protected function _assert_empty($assertion) {
        if (empty($assertion)) {
            return TRUE;
        } else {
            $this->asserts = FALSE;
            return FALSE;
        }
    }

    public function assertNotEmpty($assertion) {
        return $this->_assert_not_empty($assertion);
    }

    protected function _assert_not_empty($assertion) {
        if (!empty($assertion)) {
            return TRUE;
        } else {
            $this->asserts = FALSE;
            return FALSE;
        }
    }

    /**
     * View Template Method
     * */
    protected function _view_header() {
        $this->output->append_output(
                '<html><head>' .
                '<title>Unit test results</title>' .
                '<style type="text/css">' .
                '* { font-family: Arial, sans-serif; font-size: 9pt }' .
                '#results { width: 100% }' .
                '.err, .pas { color: white; font-weight: bold; margin: 2px 0; padding: 5px; vertical-align: top; }' .
                '.err { background-color: red }' .
                '.pas { background-color: green }' .
                '.detail { padding: 8px 0 8px 20px }' .
                'h1 { font-size: 12pt }' .
                'a:link, a:visited { text-decoration: none; color: white }' .
                'a:active, a:hover { text-decoration: none; color: black; background-color: yellow }' .
                '</style></head><body>' .
                '<h1>Toast Unit Tests:</h1>' .
                '<ol>'
        );
    }

    protected function _view_results($data) {
        $this->load->helper('language');

        $i = 0;
        foreach ($data['results'] as $result) {
            $this->output->append_output('<li>');
            if ($result[lang('ut_result')] == lang('ut_passed')) {
                $this->output->append_output('<div class="pas">[' . strtoupper(lang('ut_passed')) . '] ' . $result[lang('ut_test_name')]);
                if (!empty($data['messages'][$i])) {
                    $this->output->append_output('<div class="detail">' . $data['messages'][$i] . '&nbsp;</div>');
                }
                $this->output->append_output('</div>');
            } else {
                $this->output->append_output('<div class="err">[' . strtoupper(lang('ut_failed')) . '] ' . $result[lang('ut_test_name')]);
                if (!empty($data['messages'][$i])) {
                    $this->output->append_output('<div class="detail">' . $data['messages'][$i] . '&nbsp;</div>');
                }
                $this->output->append_output('</div>');
            }
            $this->output->append_output('</li>');
            $i++;
        }
    }

    protected function _view_footer() {
        $this->output->append_output(
                '</ol><br /><strong>All tests completed in ' .
                $this->benchmark->elapsed_time('total_execution_time_start', 'total_execution_time_end') .
                ' seconds</strong><br /><br /><br /><br /></body></html>'
        );
    }

}

// End of file Toast.php */
// Location: ./system/application/controllers/test/Toast.php */ 
