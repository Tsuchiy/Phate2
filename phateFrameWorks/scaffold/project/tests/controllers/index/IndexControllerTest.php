<?php
/**
 * @group Index
 */
class IndexControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * インデックスAPI正常系テスト
     */
    public function testIndex()
    {
        $headers = [
        ];
        $post = [
        ];
        $access = new TestHttpRequester();
        $response = $access->access('/index/Index/', $headers, $post);
        // echo $access->rtnHeader;
        // echo $access->rtnBody;
        $this->assertTrue((bool)$response);
        $this->assertEquals('Hello world', $access->rtnBody);
    }
}
