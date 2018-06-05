<?php
/*
 * @端口控制器
 */
namespace App\Http\Controllers;

use App\Channel;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\DomCrawler\Crawler;

class ChannelController extends Controller{

	public function index(){
		$channels = Channel::all()->toArray();
		return view('channels',['channels' => $channels]);
	}

	public function account(Request $request){
		$method = $request->method();

		$input = $request->all();
		$cid = $request->input('websiteId');
		$username = $request->input('username');
		$password = $request->input('password');

		$channel = Channel::find($cid);
		$loginUrl = $channel->login_url;

		$post_data['username'] = '15810883870';
        $post_data['password'] = '56edd695baad517415927f711342c64a1018b3301fb370066dfa90ecc2f3803a6a420d0e0629b4449e026c93358c749d7f3c54903845f13636f1d2ae9ca83f8341bbc8bd39709133094843d740682d4bcc731973e631b78221a18ce476996a4cccf699968e88e4cf85e4616a45b4f5215c06a4b3c71cc17686cd4cccb0743e33';
        $post_data['ckey'] = '0b6099fe4aa4c9e48ed537de7b7d54fb2b9eaa54';
        $post_data['imgcode']    = '';
        $o = "";
        foreach ( $post_data as $k => $v ) 
        { 
            $o.= "$k=" . urlencode( $v ). "&" ;
        }
        //$post_data = substr($o,0,-1);
 
        
		

      	var_dump($loginUrl);
		$refer = "http://j.esf.leju.com/ucenter/passportlogin/";
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$loginUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
		curl_setopt($ch, CURLOPT_REFERER, $refer);
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);
        
        var_dump($data);


		return 'ok';
	}

	public function login(){
		
		$curcity = 'bj';
		$url = 'http://j.esf.leju.com/ucenter/login?curcity='.$curcity;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$response = curl_exec($ch);
		curl_close($ch);



		$crawler = new Crawler();
		$crawler->addHtmlContent($response);
		
		//$aaa = $crawler->filterXPath('//input[@id="pubkey"]/@value')->text();
		//$pubkey = $crawler->filterXPath('//form[@id="agent_login"]/input[@name="pubkey"]/@value')->text();
		//$username = $crawler->filterXPath('//form[@id="agent_login"]/input[@name="username"]/@value')->text();
		//$password = $crawler->filterXPath('//form[@id="agent_login"]/input[@name="password"]/@value')->text();
		$ckey = $crawler->filterXPath('//form[@id="agent_login"]/input[@name="ckey"]/@value')->text();




		$post_data['username'] = '15810883870';
        $post_data['password'] = '56edd695baad517415927f711342c64a1018b3301fb370066dfa90ecc2f3803a6a420d0e0629b4449e026c93358c749d7f3c54903845f13636f1d2ae9ca83f8341bbc8bd39709133094843d740682d4bcc731973e631b78221a18ce476996a4cccf699968e88e4cf85e4616a45b4f5215c06a4b3c71cc17686cd4cccb0743e33';
        $post_data['ckey'] = $ckey;
        $post_data['imgcode']    = '';
        $o = "";
        foreach ( $post_data as $k => $v ) 
        { 
            $o.= "$k=" . urlencode( $v ). "&" ;
        }
        $post_data = substr($o,0,-1);
 
        
		

      	
		$refer = "http://j.esf.leju.com/ucenter/passportlogin/";
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$url);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
		curl_setopt($ch, CURLOPT_REFERER, $refer);
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);
        
        var_dump($data);

		return $response;
	}

}
