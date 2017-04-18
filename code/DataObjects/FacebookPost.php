<?php

namespace Olliepop\FBPageFeed;

/**
 * Class FacebookPost
 * @package Olliepop\FBPageFeed
 */
class FacebookPost extends \DataObject
{

    /**
     * @var array
     */
    private static $db = array(
        'FBID' => 'Varchar(100)',
        'Content' => 'Text',
        'ImageSource' => 'Varchar(255)',
        'URL' => 'Varchar(255)',
        'TimePosted' => 'SS_Datetime' // The time it was posted to the Page Timeline
    );

    private static $casting = array(
        'PostSummary' => 'HTMLText'
    );

    /**
     * @var string
     */
    private static $default_sort = 'TimePosted DESC';

    private function hyperlinkURLs($content) {
        $content = preg_replace('$(\s|^)(https?://[a-z0-9_./?=&-]+)(?![^<>]*>)$i', ' <a href="$2" target="_blank">$2</a> ', \DBField::create_field('Text', $content)->FirstParagraph(0));
        $content = preg_replace('$(\s|^)(www\.[a-z0-9_./?=&-]+)(?![^<>]*>)$i', '<a target="_blank" href="http://$2"  target="_blank">$2</a> ', \DBField::create_field('Text', $content)->FirstParagraph(0));
        return $content;
    }

    public function getPostSummary() {
        return $this->hyperlinkURLs($this->Content);
    }

}