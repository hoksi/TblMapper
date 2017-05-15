<?php

namespace hoksi;

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Korean Style Pagination
 *
 * @author Hoksi (hoksi2k@hanmail.net)
 */
class Pagination {

    /**
     * Codeigniter Object
     * 
     * @var Codeigniter
     */
    protected $ci;

    /**
     * Base URL
     * 
     * @var string
     */
    public $baseUrl;

    /**
     * Pager Group
     * 
     * @var array
     */
    protected $groups;

    /**
     * GET Query String
     * 
     * @var array
     */
    protected $qryStr;

    /**
     * Current Page
     * @var int
     */
    protected $currentPage;

    /**
     * Per Page
     * @var int
     */
    protected $perPage;

    /**
     * offset
     * @var int
     */
    protected $offset;

    /**
     * Page link Length
     * 
     * @var int
     */
    protected $linkLen;

    /**
     * Pagination Middle Range
     * 
     * @var int
     */
    protected $midRange;

    /**
     * Total
     * 
     * @var int
     */
    protected $total;

    /**
     * Total Page
     * 
     * @var int
     */
    protected $totalPage;

    /**
     * Pager
     * 
     * @var array
     */
    protected $pager = array();

    /**
     * Pagination Construct
     */
    public function __construct() {
        $this->ci = & get_instance();

        $this->ci->load->helper('url');

        $this->baseUrl = site_url($this->ci->uri->uri_string());

        $this->qryStr = $_GET;

        $this->currentPage = (isset($this->qryStr['page']) ? intval($this->qryStr['page']) : 1);
        $this->perPage = (isset($this->qryStr['per_page']) ? intval($this->qryStr['per_page']) : 20);
        $this->linkLen = (isset($this->qryStr['link_len']) ? intval($this->qryStr['link_len']) : 14);
        $this->midRange = intval(floor($this->linkLen / 2));

        unset($this->qryStr['page']);
        unset($this->qryStr['per_page']);
        unset($this->qryStr['link_len']);
    }

    /**
     * Ensures that an array exists for the group specified.
     *
     * @param string $group
     */
    protected function ensureGroup($group) {
        if (isset($this->groups[$group])) {
            return;
        }

        $this->groups[$group] = true;
    }

    /**
     * set Total length
     * 
     * @param type $total
     * @return $this
     */
    public function setTotal($total, $group = 'default') {
        $this->ensureGroup($group);

        $this->total = $total;

        $this->totalPage = $lastPage = intval(ceil($total / $this->perPage));
        $this->currentPage = ($this->currentPage > $lastPage ? $lastPage : $this->currentPage);
        $this->midRange = ($this->midRange > $lastPage ? $lastPage : $this->midRange);
        $start = $this->currentPage - $this->midRange;
        $end = $this->currentPage + $this->midRange;

        if ($start <= 0) {
            $end += abs($start) + 1;
            $start = 1;
        }

        if ($end > $lastPage) {
            $start -= ($end - $lastPage);
            $start = ($start < 1 ? 1 : $start);
            $end = $lastPage;
        }

        $prevJump = $start - ($this->midRange + 1);
        $prevJump = ($prevJump < 1 ? 1 : $prevJump);

        $nextJump = $end + $this->midRange + 1;
        $nextJump = ($nextJump > $lastPage ? $lastPage : $nextJump);

        for ($i = $start; $i <= $end; $i++) {
            $this->pager[] = $i;
        }

        $this->offset = ($this->currentPage - 1) * $this->perPage;
        $this->offset = $this->offset < 0 ? 0 : $this->offset;

        $this->pager = array(
            'firstPage' => 1,
            'prevJump' => $prevJump,
            'prevPage' => ($this->currentPage - 1 < 1 ? 1 : $this->currentPage - 1),
            'curPage' => $this->currentPage,
            'nextPage' => ($this->currentPage + 1 > $lastPage ? $lastPage : $this->currentPage + 1),
            'nextJump' => $nextJump,
            'lastPage' => $lastPage,
            'perPage' => $this->perPage,
            'total' => $this->total,
            'totalPage' => $this->totalPage,
            'pager' => $this->pager,
            'qry_str' => (!empty($this->qryStr) ? http_build_query($this->qryStr) : ''),
        );

        return $this;
    }

    /**
     * Get Pager Array
     * 
     * @return array
     */
    public function getPagerArray($group = 'default') {
        $this->ensureGroup($group);

        return $this->pager;
    }

    /**
     * Stores a set of pagination data for later display. Most commonly used
     * by the model to automate the process.
     *
     * @param string $group
     * @param int    $page
     * @param int    $perPage
     * @param int    $total
     *
     * @return mixed
     */
    public function store($group, $page, $perPage, $total) {
        $this->ensureGroup($group);

        $this->currentPage = $page;
        $this->perPage = $perPage;

        $this->setTotal($total, $group);

        return $this;
    }

    /**
     * Returns the number of the current page of results.
     *
     * @param string|null $group
     *
     * @return int
     */
    public function getCurrentPage($group = 'default') {
        $this->ensureGroup($group);

        return $this->currentPage;
    }

    /**
     * Returns an array with details about the results, including
     * total, per_page, current_page, last_page, next_url, prev_url, from, to.
     * Does not include the actual data. This data is suitable for adding
     * a 'data' object to with the result set and converting to JSON.
     *
     * @param string $group
     *
     * @return array
     */
    public function getDetails($group = 'default') {
        return $this->getPagerArray($group);
    }

    /**
     * Determines the first page # that should be shown.
     *
     * @param string $group
     *
     * @return int
     */
    public function getFirstPage($group = 'default') {
        $this->ensureGroup($group);

        // @todo determine based on a 'surroundCount' value
        return 1;
    }

    /**
     * Returns the last page, if we have a total that we can calculate with.
     *
     * @param string $group
     *
     * @return int|null
     */
    public function getLastPage($group = 'default') {
        $this->ensureGroup($group);

        return (isset($this->pager['lastPage']) ? $this->pager['lastPage'] : false);
    }

    /**
     * Returns the full URI to the next page of results, or null.
     *
     * @param string $group
     * @param bool   $returnObject
     *
     * @return string|null
     */
    public function getNextPageURI($group = 'default', $returnObject = false) {
        return (isset($this->pager['nextPage']) ? $this->getPageURI($this->pager['nextPage'], $group, $returnObject) : '');
    }

    /**
     * Returns the URI for a specific page for the specified group.
     *
     * @param int    $page
     * @param string $group
     * @param bool   $returnObject
     *
     * @return string
     */
    public function getPageURI($page = null, $group = 'default', $returnObject = false) {
        $this->ensureGroup($group);

        $this->qryStr['page'] = $page;

        return $this->baseUrl . '?' . http_build_query($this->qryStr);
    }

    /**
     * Returns the total number of pages.
     *
     * @param string|null $group
     *
     * @return int
     */
    public function getPageCount($group = 'default') {
        $this->ensureGroup($group);

        return (isset($this->pager['totalPage']) ? $this->pager['totalPage'] : false);
    }

    /**
     * Returns the number of results per page that should be shown.
     *
     * @param string $group
     *
     * @return int
     */
    public function getPerPage($group = 'default') {
        $this->ensureGroup($group);

        return $this->pager['perPage'];
    }

    /**
     * Returns the full URL to the previous page of results, or null.
     *
     * @param string $group
     * @param bool   $returnObject
     *
     * @return string|null
     */
    public function getPreviousPageURI($group = 'default', $returnObject = false) {
        return (isset($this->pager['prevPage']) ? $this->getPageURI($this->pager['prevPage'], $group, $returnObject) : '');
    }

    /**
     * Tells whether this group of results has any more pages of results.
     *
     * @param string|null $group
     *
     * @return bool
     */
    public function hasMore($group = 'default') {
        $this->ensureGroup($group);

        return ($this->currentPage * $this->perPage) < $this->
    public function links($group = 'total;
    }

    /**
     * Handles creating and displaying the
     *
     * @param string $group
     * @param string $template The output template alias to render.
     *
     * @return string
     */default', $template = 'default_full') {
        $this->ensureGroup($group);

        return $this->displayLinks($group, $template);
    }

    /**
     * Does the actual work of displaying the view file. Used internally
     * by links(), simpleLinks(), and makeLinks().
     *
     * @param string $group
     * @param string $template
     *
     * @return string
     */
    protected function displayLinks($group, $template) {
        $this->ci->load->vars(array('pager' => $this->pager));
        
        $this->ci->load->file(__DIR__ . '/views/pagination/' . $template . '.php');
    }

}
