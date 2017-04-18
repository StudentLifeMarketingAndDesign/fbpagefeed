<?php

namespace Olliepop\FBPageFeed;

use Facebook;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Exceptions\FacebookResponseException;

/**
 * Class FBPageFeedService
 * @package Olliepop\FBPageFeed
 */
class FBPageFeedService
{

    /**
     * @var \Facebook\Facebook
     */
    private $fb;
    /**
     * @var mixed
     */
    private $appID;
    /**
     * @var mixed
     */
    private $appSecret;
    /**
     * @var mixed|null
     */
    private $pageID;
    /**
     * @var mixed
     */
    private $accessToken;

    /**
     * @param null $pageID The Facebook ID of the page, can be obtained at http://findmyfacebookid.com
     */
    function __construct($pageID = null)
    {
        $siteConfig = \SiteConfig::current_site_config();

        if(!$pageID) {
            $pageID = $siteConfig->FBPageID;
        }

        $this->pageID = $pageID;
        $this->appID = $siteConfig->FBAppID;
        $this->appSecret = $siteConfig->FBAppSecret;
        $this->accessToken = $siteConfig->FBAccessToken;

        $fb = new Facebook\Facebook([
          'app_id'     => $this->appID,
          'app_secret' => $this->appSecret,
          'default_graph_version' => 'v2.8',
          ]);

        $fb->setDefaultAccessToken($this->accessToken);
        $this->fb = $fb;

    }

    /**
     * Get our local copies of the Facebook Page posts
     *
     * @param int $limit
     * @return \DataList|\SS_Limitable
     */
    public function getStoredPosts($limit = 4)
    {
        return FacebookPost::get()->limit($limit);
    }

    /**
     * Store a Facebook Page post into our database
     *
     * @param $fb_id
     * @param $content
     * @param $url
     * @param $timePosted
     * @param null $imageSource
     * @return FacebookPost
     */
    public function storePost($fb_id, $content, $url, $timePosted, $imageSource = null)
    {
        $fbPost = new FacebookPost();
        $fbPost->FBID = $fb_id;
        $fbPost->Content = $content;
        $fbPost->TimePosted = $timePosted;
        $fbPost->URL = $url;
        if($imageSource) $fbPost->ImageSource = $imageSource;
        $fbPost->write();

        return $fbPost;
    }

    /**
     * Retrieve Facebook Page posts using the Facebook RESTful API
     *
     * @param int $limit
     * @return array|bool
     */
    public function getPostsFromFacebook($limit = 4)
    {
        $posts = array();
        $fb = $this->fb;
        try {   
            $response = $fb->get('/' . $this->pageID . '/feed?fields=id,message,link,object_id,created_time,type,picture');
            $pagefeed = $response->getDecodedBody();
            //print_r($pagefeed['data']);
            foreach($pagefeed['data'] as $iteration=>$responseData) {
                //print_r($responseData);
                if($iteration==$limit) break;

                if(isset($responseData['message'])) {

                    $posts[$iteration]['Content'] = $responseData['message'];
                    $posts[$iteration]['FBID'] = $responseData['id'];
                    $posts[$iteration]['URL'] = $responseData['link'];
                    $posts[$iteration]['source'] = $responseData['picture'];
                    $posts[$iteration]['TimePosted'] = $responseData['created_time'];
                }

                if($responseData['type'] == "photo") {
                    if(isset($responseData['object_id'])) {

                        $subRequest = $fb->get( '/' . $responseData['object_id'] . '?fields=images');
                           
                        $subResponse = $subRequest->getDecodedBody();
                        //print_r($subResponse);
                        // Get the largest image for best quality
                        $images = $subResponse['images'];
                        $largestWidth = 0;
                        $largestIndex = 0;
                        // Loop through each supplied image object, remembering the largest
                        foreach($images as $index=>$image) {
                            if($image['width'] > $largestWidth) {
                                $largestIndex = $index;
                                $largestWidth = $image['width'];
                            }
                        }
                        //print_r($images);
                        // Cherry-pick the source of the largest image asset
                        $posts[$iteration]['source'] = $images{$largestIndex}['source'];
                    }
                }

            }
          
            return $posts;
        } catch (FacebookResponseException $e) {
            // The Graph API returned an error
            error_log('Olliepop\LGPageFeed SilverStripe Module Exception #1: ' . $e);
        } catch (FacebookSDKException $e) {
            // Some othFacebookSDKExceptioner error occurred
            error_log('Olliepop\LGPageFeed SilverStripe Module Exception #2: ' . $e);
        }

        return false;
    }

}