<?php
	/*
		한국 ip 대역 호출

		115.68.45.210 - /home/miraedev/batch/getKoreaIp.sh 실행해서 korea_ip.php 파일을 생성을 한다.
		해당 파일을 각 L4 에 복사를 해서 사용중이다.
	*/
	@include $_SERVER['DOCUMENT_ROOT'] ."/include/korea_ip.php";

	// 한국 ip 체크
	if ( is_array($korea_ip) && isset($korea_ip) ) {
		$my_ips = explode("." , $_SERVER['REMOTE_ADDR'] );

		// 해외 ip 시 종료
		if ( !isset($korea_ip[$my_ips[0]][$my_ips[1]]) ){
			$page_arr = explode("/" , $_SERVER['PHP_SELF'] );

			// 미톡에서 호출시
			if ( in_array( $page_arr[count($page_arr)-1] , array ( 'mirae_response_talk.php' , 'mirae_response_talk_dev.php') ) ) {
				if ( function_exists("echoJson") ) {
					echoJson("ip is global");
				}
			}
			exit; 
		}
	}
?>