<?php
/*
 * @房产控制器
 */
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\DomCrawler\Crawler;

class HouseController extends Controller{
	public function collect(Request $request){
		$method = $request->method();
		$url = $request->input('collect_url');
		$url = "https://bj.esf.leju.com/detail/271201602/#zn=pc-house-15";
		if(empty($url)){
			return 'error';	
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$response = curl_exec($ch);
		curl_close($ch);

		$crawler = new Crawler();
		$crawler->addHtmlContent($response);

		$crawler->filterXPath('//div[@class="h-pro-con"]/img/@src')->each(function(Crawler $node, $i){
			var_dump($node->text());
		});
		

		return $response;
	}
}
