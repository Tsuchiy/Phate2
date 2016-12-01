<?php
/**
 * @group developmentTool
 */
class DevelopmentToolTest extends PHPUnit_Framework_TestCase
{
    /**
     * ブラウザの代わり
     */
    public function testDevelop()
    {
        $accessPoint = '/index/Index/';
        
        $access = new TestHttpRequester();

        // $access->login();
        $headers = [
        ];
        $post = [
        ];
        $response = $access->access($accessPoint, $headers, $post);
        // echo $access->rtnHeader;
        // echo $access->rtnBody;
        $this->assertTrue((bool)$response);
    }
}
