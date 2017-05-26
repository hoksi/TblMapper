<?php

namespace hoksi;

/**
 * Codeigniter4 Frend PagerRenderer
 *
 * @author hoksi2k@hanmail.net
 */
class PagerRenderer {

    /**
     * Page Detail
     * 
     * @var array
     */
    protected $details;

    /**
     * Construct
     * 
     * @param array $details
     */
    public function __construct($details) {
        $this->details = $details;
    }

    /**
     * Sets the total number of links that should appear on either
     * side of the current page. Adjusts the first and last counts
     * to reflect it.
     *
     * @param int $count
     *
     * @return $this
     */
    public function setSurroundCount($count) {
        return $this;
    }

    /**
     * Checks to see if there is a "previous" page before our "first" page.
     *
     * @return bool
     */
    public function hasPrevious() {
        return $this->details['curPage'] != $this->details['prevPage'];
    }

    /**
     * Returns a URL to the "previous" page. The previous page is NOT the
     * page before the current page, but is the page just before the
     * "first" page.
     *
     * You MUST call hasPrevious() first, or this value may be invalid.
     *
     * @return string
     */
    public function getPrevious() {

        return $this->makePageUrl($this->details['prevPage']);
    }

    /**
     * Checks to see if there is a "next" page after our "last" page.
     *
     * @return bool
     */
    public function hasNext() {
        return $this->details['curPage'] != $this->details['nextPage'];
    }

    /**
     * Returns a URL to the "next" page. The next page is NOT, the
     * page after the current page, but is the page that follows the
     * "last" page.
     *
     * You MUST call hasNext() first, or this value may be invalid.
     *
     * @return string
     */
    public function getNext() {
        return $this->makePageUrl($this->details['nextPage']);
    }

    /**
     * Returns the URI of the first page.
     *
     * @return string
     */
    public function getFirst() {
        return $this->makePageUrl($this->details['firstPage']);
    }

    /**
     * Returns the URI of the last page.
     *
     * @return string
     */
    public function getLast() {
        return $this->details['lastPage'];
    }

    /**
     * Returns an array of links that should be displayed. Each link
     * is represented by another array containing of the URI the link
     * should go to, the title (number) of the link, and a boolean
     * value representing whether this link is active or not.
     *
     * @return array
     */
    public function links() {
        foreach ($this->details['pager'] as $page) {
            $links[] = [
                'uri' => $this->makePageUrl($page),
                'title' => $page,
                'active' => ($page == $this->details['curPage'])
            ];
        }

        return $links;
    }

    /**
     * Make Page Url
     * 
     * @param int $page
     * @return string
     */
    protected function makePageUrl($page) {
        return $this->details['url'] . '?' . $this->details['qry_str'] . '&page=' . $page;
    }

}
