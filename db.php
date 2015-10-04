<?php

//判定
define('OK',0);
define ('NG',-1);
//エラーコード
define ('NO_ERROR', 0);
define ('ERROR', -1);
define('DB_CREATE_TABLE_ERROR', 99);
define ('DB_MAKE_ERROR', 100);
define ('DB_ADD_ERROR',	101);
define('DB_ADD_ARRAY_ERROR', 102);
define('DB_ADD_MENU_OPEN_ERROR', 103);
define('DB_ADD_MENU_EMPTY', 104);
define('DB_ADD_MENU_ERROR', 90);
define('DB_ADD_ORDER_ERROR', 105);
define('DB_ADD_ORDER_DUPLICATE_ERROR', 106);
define('DB_UPDATE_ERROR', 110);
define('DB_DELETE_ERROR', 120);
define('NOT_NUMERIC_ERROR',300);//数値ではない
define('DB_ACCESS_ERROR', 301);//DBのアクセス時にエラー発生
define('DB_FETCH_EMPTY_ERROR',500);
define('DB_GET_MENU_ERROR', 400);
//DBの名前
define('DATABASE_NAME', 'db.db');
class DB{

	private $db = null;

	//コンストラクタ
	function __construct(){
		try{
			$this->db = new PDO('sqlite:'.DATABASE_NAME);
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			die ('Connection failed : '.$e->getMessage());
		}
		$this->initalize();
	}
	//デストラクタ
	function __destruct(){
		unset($this->db);
	}
	/**
	 *Function: initialize(void)
	 *Arguments	  : なし
	 *Return        : OK|NG
	 *Date          : 2015/09/25
	 *Comment  :
	 */
	public function initalize(){
		$sql = 'CREATE TABLE IF NOT EXISTS `order` ('.
  						"`orderNo` INTEGER PRIMARY KEY UNIQUE NOT NULL,".
  						"`orderQuery` TEXT NOT NULL,".
  						"`orderDate` INTEGER NOT NULL,".
  						"`complete` INTEGER NOT NULL DEFAULT 0,".
  						"`completeDate` INTEGER); ".
  				"CREATE TABLE IF NOT EXISTS `menu` (".
  						"`id` INTEGER PRIMARY KEY UNIQUE NOT NULL ,".
  						"`menu_full` TEXT NOT NULL,".
  						"`explain` TEXT NOT NULL,".
  						"`price` INTEGER NOT NULL,".
  						"`sold_out` INTEGER NOT NULL DEFAULT 0);";
			if($this->exec($sql) == NG)return NG;
			return OK;
	}
	function error($e, $errorCode){
		echo $e->getTraceAsString();
		echo $e->getMessage();
		return $errorCode;
	}

	/**
	 *Function: exec($sql)
	 *Arguments	  : string $sql::sqlQueryString
	 *Return        : void
	 *Date          : 2015/09/27
	 *Comment  :
	 */
	private function exec($sql){
		try{
			$this->db->exec($sql);
		}catch(Exception $e){
			//echo "<!--".$e->getTraceAsString()."-->";
			//デバッグ用
			echo $e->getTraceAsString();
			echo $e->getMessage();
			$this->fatalError($e);
		}
		return;
	}
	/**
	 *Function: query($sql, &$obj)
	 *Arguments	  :	string $sql::sqlQueryString
	 *							object $obj::SQLクエリ実行時の返却オブジェクト
	 *Return        : void
	 *Date          : 2015/09/27
	 *Comment  :
	 */
	private function query($sql,&$obj){
		try{
			$obj= $this->db->query($sql);
		}catch(Exception $e){
			echo "<!--".$e->getTraceAsString()."-->";
			$this->fatalError($e);
		}
		return;
	}
	function fatalError($e){
		die();
	}
	/**
	 *Function: numericCheck(&$any)
	 *Arguments	  :	var $any::なんでもよい
	 *Return        : true|false
	 *Date          : 2015/09/27
	 *Comment  :数値かどうかを判定する(今後機能追加の可能性あるため関数化)
	 */
	public function numericCheck(&$any){
		if(is_numeric($any)){
			$any = intval($any);
			return true;
		}
		return false;
	}
	/**【未実装】
	 *Function: escapt(&$str)
	 *Arguments	  :	string $str::エスケープする文字列
	 *Return        : true|false
	 *Date          : 2015/09/25
	 *Comment  :文字列をエスケープ(今後機能追加の可能性あるため関数化)
	 */
	private function escape(&$str){
		$res = strip_tags($str);
		$res = htmlspecialchars($res, ENT_QUOTES);
		$str = $res;
		return true;
	}
	/**
	 *Function:deleteSpace(&$str)
	 *Arguments	  :	string		$str::文字列
	 *Return        : void
	 *Date          : 2015/09/25
	 *Comment  :$strから全角・半角sp・タブを除去
	 */
	static public function deleteSpace(&$str){
		preg_replace('/(\s|　|\t)/', '', $str);
		return;
	}

	/**
	 *Function: is_menu($menuID, &$result)
	 *Arguments	  :	int	$menuID::メニュー識別子
	 *							bool	$result::存在(true|false)
	 *Return        : ERROR_CODE|NO_ERROR
	 *Date          : 2015/09/25
	 *Comment  :メニューがすでに存在するか？
	 */
	public function is_menu($menuID, &$result){
			if(!$this->numericCheck($menuID))return NOT_NUMERIC_ERROR;
			$sql = sprintf("SELECT * FROM 'menu' WHERE id=%d;", $menuID);
			$this->fetch($sql, $rows);
			try{
				if($rows == FALSE) throw new Exception('DBfetchError'.__FUNCTION__);
			}catch(Exception $e){
				$this->fatalError($e);
			}
			$result = (count($rows) > 0 && $rows[0]["id"] == $menuID)?true:false;
			return NO_ERROR;
	}
	/**
	 *Function: addMenu($menuID, $menu_full)
	 *Arguments	  :	int		$menuID::メニューの識別子
	 *							string	$menu_full::メニューの正式名称
	 *							string	$explain::メニューの説明
	 *							int		$price::価格
	 *Return        : ERROR_CODE|NO_ERROR
	 *Date          : 2015/09/25
	 *Comment  :
	 */
	public function addMenu($menuID, $menu_full, $explain, $price){
		if(!($this->numericCheck($menuID))&& !($this->numericCheck($price)))return DB_ADD_MENU_ERROR;
		$sql = sprintf("INSERT INTO menu(id, menu_full, explain, price ) VALUES (%d,'%s', '%s', %d);", $menuID, $menu_full, $explain, $price);
		$this->exec($sql);
		return NO_ERROR;
	}
	/**
	 *Function: deleteMenu($menuID)
	 *Arguments	  :	int	$menuID::メニューの識別子
	 *Return        : ERROR_CODE|NO_ERROR
	 *Date          : 2015/09/25
	 *Comment  :
	 */
	public function deleteMenu($menuID){
		if(!$this->numericCheck($menuID))return DB_DELETE_ERROR;
		$sql = sprintf("DELETE FROM menu WHERE id = %d", $menuID);
		$this->exec($sql);
		return NO_ERROR;
	}
	/**
	 *Function: updateMenuSoldOut($menuID)
	 *Arguments	  :	int	$menuID::メニューの識別子
	 *Return        : ERROR_CODE|NO_ERROR
	 *Date          : 2015/09/25
	 *Comment  :メニューのSoldOutを1にする
	 */
	public function updateMenuSoldOut($menuID){
		if(!$this->numericCheck($menuID))return DB_UPDATE_ERROR;
		$sql = sprintf("UPDATE menu SET sold_out = 1 WHERE id = %d" ,$menuID);
		$this->db->exec($sql);
		return NO_ERROR;
	}
	/**
	 *Function: updateMenuPriceChange($menuID, $price)
	 *Arguments	  :	int	$menuID::メニューの識別子
	 *							int	$price::改定後の価格
	 *Return        : ERROR_CODE|NO_ERROR
	 *Date          : 2015/09/25
	 *Comment  :メニューの	価格を変更する
	 */
	public function updateMenuPriceChange($menuID, $price){
		if(!$this->numericCheck($menuID)  && $this->numericCheck($price) )return DB_UPDATE_ERROR;
		$sql = sprintf("UPDATE menu SET price=%d WHERE id = %d" ,$price, $menuID);
		$this->exec($sql);
		return NO_ERROR;
	}
	/**
	 *Function: updateMenuFullName($menuID, $menu_full)
	 *Arguments	  :	int		$menuID::メニューの識別子
	 *							string	$menu_full::メニューの正式名称
	 *Return        : ERROR_CODE|NO_ERROR
	 *Date          : 2015/09/25
	 *Comment  :メニューの正式名称を変更する
	 */
	public function updateMenuFullName($menuID,$menu_full){
		if(!$this->numericCheck($menuID) )return DB_UPDATE_ERROR;
		$sql = sprintf("UPDATE menu SET menu_full = '%s' WHERE id = '%d'" ,$menu_full, $menu);
		$this->exec($sql);
		return NO_ERROR;
	}
	/**
	 *Function: updateMenuExplain($menuID, $explain)
	 *Arguments	  :	char		$menuID::メニューの識別子
	 *							string	$menu_full::メニューの説明
	 *Return        : ERROR_CODE|NO_ERROR
	 *Date          : 2015/09/25
	 *Comment  :メニューの説明を変更する
	 */
	public function updateMenu($menuID,$menu_full){
		if(!$this->numericCheck($menuID))return DB_UPDATE_ERROR;
		$sql = sprintf("UPDATE menu SET explain = '%s' WHERE id = '%d'" ,$explain, $menu);
		$this->exec($sql);
		return NO_ERROR;
	}
	/**
	 *Function: addOrderByString($orderStr, &$orderNo)
	 *Arguments	  :	string	$orderStr::オーダー
	 *							int		$orderNo::オーダー番号の返却値
	 *Return        : ERROR_CODE|NO_ERROR
	 *Date          : 2015/09/25
	 *Comment  :stringでオーダーを追加する
	 */
	public function addOrderByString($orderStr, &$orderNo){
		if(!(isset($orderStr)))return DB_ADD_ORDER_ERROR;
		$this->parseOrderToArray($orderStr,$array);
		return $this->addOrderByArray($array, $orderNo);
	}
	/**
	 *Function: addOrderByArray($order,$orderNo)
	 *Arguments	  :	array	$order::オーダー配列
	 *							int		$orderNo::オーダー番号の返却値
	 *Return        : ERROR_CODE|NO_ERROR
	 *Date          : 2015/09/25
	 *Comment  :arrayでオーダーを追加する
	 *ex:"m1o3m2o2"
	 *		array{
	 *			[1]=>3
	 *			[2]=>2
	 *		}
	 */

	public function addOrderByArray($order, $orderNo){
		if(!(isset($order) && is_array($order))) return DB_ADD_ORDER_ERROR;
		if(!is_array($order))return DB_ADD_ARRAY_ERROR;
		foreach ($order as $key =>$val){//メニューの存在確認
			if($this->is_menu($key,$res) != NO_ERROR || $res == false)return DB_ADD_ERROR;//throws Exception
		}
		$order_str = $this->parseOrderToString($order);
		$time = time();
		try{
			//トランザクション開始
			$this->db->exec("BEGIN DEFERRED;");
			$sql = sprintf("INSERT INTO 'order'('orderQuery', 'orderDate', 'complete') values ('%s', %d, 0);",  $order_str, $time);
			$this->db->exec($sql);
			$sql = sprintf("SELECT * FROM 'order' WHERE orderDate = %d;", $time);
			$obj = $this->db->query($sql);
			$rows = $obj->fetchAll();
			if($rows == FALSE) throw new Exception('DBfetchError'.__FUNCTION__);
			$orderNo = $rows[0]['orderNo'];
		}catch(Exception $e){
			// ロールバック
			$this->db->exec("ROLLBACK;");
			$this->fatalError($e);
		}
		// コミット
		$this->db->exec("COMMIT;");
		return NO_ERROR;
	}
	/**
	 *Function:deleteOrder($orderNo)
	 *Arguments	  :	int		$orderNo::オーダー番号
	 *Return        : ERROR_CODE|NO_ERROR
	 *Date          : 2015/09/25
	 *Comment  :未完成のオーダーを削除する
	 */
	public function deleteOrder($orderNo){
		if(!$this->numericCheck($orderNo))return DB_DELETE_ERROR;
		$sql = sprintf("DELETE FROM 'order' WHERE (orderNo = %d AND complete = 0);", $orderNo);
		$this->exec($sql);
		return NO_ERROR;
	}
	/**
	 *Function:updateOrderStatusToComplete($orderNo)
	 *Arguments	  :	int		$orderNo::オーダー番号
	 *Return        : ERROR_CODE|NO_ERROR
	 *Date          : 2015/09/25
	 *Comment  :オーダーステータスを「完了」にする
	 */
	public function updateOrderStatusToComplete($orderNo){
		if(!$this->numericCheck($orderNo))return DB_UPDATE_ERROR;
		$time = time();
		$sql = sprintf("UPDATE 'order' SET complete = 1, completeDate=%d WHERE (orderNo = %d AND complete = 0);" ,$time, $orderNo);
		$this->exec($sql);
		return NO_ERROR;
	}
	/**
	 *Function:updateOrderStatusToNotComplete($orderNo)
	 *Arguments	  :	int		$orderNo::オーダー番号
	 *Return        : ERROR_CODE|NO_ERROR
	 *Date          : 2015/09/27
	 *Comment  :オーダーステータスを「未完了」にする
	 */
	public function updateOrderStatusToNotComplete($orderNo){
		if(!$this->numericCheck($orderNo))return DB_UPDATE_ERROR;
		$sql = sprintf("UPDATE 'order' SET complete = 0、completeDate = NULL WHERE (orderNo = %d AND complete = 1);" ,$orderNo);
		$this->exec($sql);
		return NO_ERROR;
	}
	/**
	 *Function:updateOrderByString($orderNo, $orderStr)
	 *Arguments	  :	int		$orderNo::オーダー番号
	 *							string	$orderStr::オーダー
	 *Return        : ERROR_CODE|NO_ERROR
	 *Date          : 2015/09/27
	 *Comment  :オーダーを更新する
	 */
	function updateOrderByString($orderNo, $orderStr){
		if(!$this->numericCheck($orderNo))return DB_UPDATE_ERROR;
		$this->escape($orderStr);
		$sql = sprintf("UPDATE 'order' SET orderQuery = '%s' WHERE OorderNo=%d;",$orderStr, $orderNo);
		$this->exec($sql);
		return NO_ERROR;
	}
	/**
	 *Function:updateOrderByArray($orderNo, $orderArray)
	 *Arguments	  :	int		$orderNo::オーダー番号
	 *							array	$orderArray::オーダー
	 *Return        : ERROR_CODE|NO_ERROR
	 *Date          : 2015/09/27
	 *Comment  :オーダーを更新する
	 */
	function updateOrderByArray($orderNo, $orderArray){
		$orderStr = $this->parseOrderToString($orderArray);
		return $this->updateOrderByString($orderNo, $orderStr);
	}
	/**
	 *Function: getMenu($MenuID)
	 *Arguments	  :	int		$MenuID::メニュー識別子
	 *Return        : array()
	 *Date          : 2015/09/27
	 *Comment  :メニュー情報を取得する
	 *		array{
	 *			['id']=>1
	 *			['menu_full']=>fullMenuName
	 *			['explain’]=>メニュー説明
	 *			['price']=>150
	 *		}
	 */
	function getMenu($menuID){

		if(!$this->numericCheck($menuID)) return NOT_NUMERIC_ERROR;
		if(($this->is_menu($menuID, $result))!=NO_ERROR || $result == false) return  DB_GET_MENU_ERROR;
		$sql = sprintf("SELECT * FROM menu WHERE id='%d';", $menuID);
		$this->fetch($sql, $rows);
		if($rows == FALSE) throw new Exception('DBfetchError'.__FUNCTION__);
		return $rows[0];
	}
	/**
	 *Function: getMenuAll()
	 *Arguments	  :	void
	 *Return        : array()
	 *Date          : 2015/09/27
	 *Comment  :すべてのメニュー情報を取得する
	 *		array{
	 *			[０] => array{
	 *							['id']=>1
	 *							['menu_full']=>fullMenuName
	 *							['explain’]=>メニュー説明
	 *							['price']=>150
	 *						}
	 *			[1] =>...
	 *		}
	 */
	function getMenuAll(){
		$sql = sprintf("SELECT * FROM menu ;");
		$this->query($sql, $result);
		$array = $result->fetchAll();
		return $array;
	}
	/**
	 *Function:getOrderStatus($orderNo, &$retuenArray)
	 *Arguments	  :	int		$orderNo::オーダー番号
	 *							array	$returnArray::結果を返す
	 *Return        : ERROR_CODE|NO_ERROR
	 *Date          : 2015/09/25
	 *Comment  :指定されたオーダー番号のオーダー情報を返す
	 *
		array(1) {
  		  array(5) {
    		["orderNo"]=>
    			string(1) "1"
    		["orderQuery"]=>
    			string(8) "m1o2m2o1"
    		["orderDate"]=>
    			string(10) "1443202795"
    		["complete"]=>
    			string(1) "1"
    		["completeDate"]=>
    			string(10) "1443202825"
		}
	 */
	function fetch($sql, &$returnArray){

		$this->query($sql, $result);
		$returnArray = $result->fetchAll();
		if($returnArray == FALSE) throw new Exception('DBfetchError'.__FUNCTION__);
		if(count($returnArray) == 0)return DB_FETCH_EMPTY_ERROR;
		return NO_ERROR;
	}
	function getOrder($orderNo, &$returnArray){
		$sql = sprintf("SELECT * FROM 'order' WHERE orderNo=%d;", $orderNo);
		$this->fetch($sql, $returnArray);
			$returnArray = $returnArray[0];
		return OK;
	}

	function getOrderAll(){
		$sql = sprintf("SELECT * FROM 'order' WHERE complete = 0;");
		$this->fetch($sql, $returnArray);
		return $returnArray;
	}

	function parseOrderToString($orderArray){
		$order_str = "";
		foreach($orderArray as $key => $val){//登録
			$order_str .= 'm'.$key.'o'.$val;
		}
		return $order_str;
	}

	public static function parseOrderToArray($order, &$returnArray){
		$arr = array();
		$i = 0;
		while($i < strlen($order)){
			if($order[$i] == 'm'){
				$orderStr = 0;
				for(++$i;$i < strlen($order) && is_numeric($order[$i]);$i++){
					$orderStr *= 10;
					$orderStr += $order[$i];
				}
				if($order[$i] == 'o'){
					$orderNum = 0;
					if($order[++$i] == "-"){
						for(++$i;$i < strlen($order) && is_numeric($order[$i]);$i++){
							$orderNum *= 10;
							$orderNum -= $order[$i];
						}
					}
					else{
						for($i;$i < strlen($order) && is_numeric($order[$i]);$i++){
							$orderNum *= 10;
							$orderNum += $order[$i];
						}
					}
				}
				if(!array_key_exists($orderStr, $arr)) $arr += array($orderStr => 0);
				$arr[$orderStr] += $orderNum;
			}
			else{
				$i++;
			}
		}
		$returnArray = $arr;
		return OK;
	}
	function countOrtderNumAll(&$returnArray){
		$order = $this->getOrderAll();
		$ordertxt = "";
		for($i = 0; $i <count($order);$i++){
			$ordertxt .= $order[$i]['orderQuery'];
		}
		if($this->parseOrderToArray($ordertxt, $returnArray) == NG)return NG;
	}
	function countOrtderNum($orderNo, &$returnArray){
		$order = $this->getOrder($orderNo);
			$ordertxt .= $order[0]['orderQuery'];
		if($this->parseOrderToArray($ordertxt, $returnArray) == NG)return NG;
	}
}

class Order{
	private $db;//->getOrder($num, $order);
	public $orderNo;
	public $orderQuery;
	public $orderDate;
	public $complete;
	public $completeDate;
	function __construct($num){
		$this->db = new DB();
		$order = $this->db->getOrder($num);
		$this->orderNo = $order['orderNo'];
		$this->orderQuery = $order['orderQuery'];
		$this->orderDate = $order['orderDate'];
		$this->complete = $order['complete'];
		$this->completeDate = $order['completeDate'];
	}
}
//FileName
define('FORM_ORDER', 'orderform.php');
define('ADD_ORDER_PHP','addorder.php');//オーダー追加用のphpファイル名
//define('ADD_ORDER_HTML', 'addorder.html');//オーダー追加用小野HTMLファイル名
define('DEL_ORDER_HTML', 'deleteorder.html');//オーダー削除HTML
define('DEL_ORDER_PHP', 'delorder.php');//オーダー削除PHP
define('COMPLETE_ORDER_PHP', 'completeorder.php');//オーダー完了
define('UPD_ORDER_PHP', 'updateorder.php');
define('DETAIL_ORDER', 'orderdetail.php');
define('ADD_MENU_PHP','addmenu.php');
define('ADD_MENU_HTML','addmenu.html');
define('INDEX_PAGE', 'index.html');
define('VIEW_ORDER', 'top.php');
define('QUEUE_ORDER', 'queue.php');
define('VIEW_MENU', 'menu.php');
class HTML{
	private $db = null;
	private $menu;
	private $order;
	function __construct(){
		try{
			$this->db = new DB();
			if (($this->menu = $this->db->getMenuAll()) == NG) throw Exception("");
			if (($this->order = $this->db->getOrderAll())== NG) throw Exceptio("");
		}catch(Exception $e){
			print $e->getTraceAsString();
		}
	}
	function __destruct(){
		unset($this->db);
	}
	function getHtmlHeader($title){
		$header =  <<<EOM
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8"/>
		<title>$title</title>
		<link rel="stylesheet" href=""/>
	</head>
	<body>
EOM;
		return $header;
	}
	function drawHtmlHeader($title){
		header("Content-Type:text/html; charset=utf-8");
		echo $this->getHtmlHeader($title);
	}
	function getHtmlFooter(){
		$footer =  <<<EOM
	</body>
</html>
EOM;
		return $footer;
	}
	function drawHtmlFooter(){
		echo $this->getHtmlFooter();
	}


	function drawOrder($num){
		$this->db->getOrder($num, $order);
		$orderNo = $order['orderNo'];
		$orderQuery = $order['orderQuery'];
		$orderDate = $order['orderDate'];
		$complete = $order['complete'];
		$completeDate = $order['completeDate'];

		$html ="";
		$html .=<<<EOM
		<table border="1">
		<thead>
			<tr><caption>
EOM;
			if($orderNo != $num)continue;
			$html .=$orderNo;
			$html .= <<<EOM
			</caption></tr>
					<th>オーダー内容</th><th>個数</th>
		</thead>
		<tbody>
		<tr>
EOM;
			$this->db->parseOrderToArray($orderQuery,$array);
			ksort($array);
			foreach($array as $key => $val){
				$menu = $this->db->getMenu($key);
				$menu = $menu["menu_full"];
				$html .=<<<EOM
				<tr><td>$menu</td>
				<td>$val\0個</td>
				</tr>
EOM;
			}
			$html .=<<<EOM
			</tr>
			<tr>
			<td>
				<script>
					function conf(txt, loc){
						if(confirm(txt)){
							if(confirm('本当によろしいですか？ >'+txt)){
								var f = document.createElement("form");
								f.method = "POST";
								f.action = loc;
								var data = document.createElement("input");
								data.type="hidden";
								data.name="orderNo";
								data.value=$orderNo;
								f.appendChild(data);
								f.submit();
							}
						}
					}
				</script>
EOM;
				$html .= "<button onclick=\"conf('完了します','".COMPLETE_ORDER_PHP."')\">完了</button>\n";
				$html .= "<button onclick=\"conf('取り消しします','".DEL_ORDER_PHP."')\">取消</button>\n";
				$html .= <<<EOM
				</td>
			</tr>
			</tbody></table>
EOM;
		echo $html;
	}
	function drawOrderAll(){
		$html ="";
		for($i = 0; $i <count($this->order);$i++){
			$orderNo = $this->order[$i]["orderNo"];
			$html .=<<<EOM
		<table border="1">
		<thead>
			<tr><caption>$orderNo</caption></tr>
					<th>オーダー内容</th><th>個数</th>	<th>
EOM;
			$html .= "<form action=\"".DETAIL_ORDER."\" method=\"POST\">";
			$html .= <<<EOM
					<input type="hidden" name="orderNo" value="$orderNo" />
					<input type="submit" value="詳細" />
				</form>
				</th>
		</thead>
		<tbody>
			<tr>
EOM;
			$this->db->parseOrderToArray($this->order[$i]["orderQuery"],$array);
			ksort($array);
			foreach($array as $key => $val){
				$html .= "<tr><td>";
				$menu = $this->db->getMenu($key);
				$html .= $menu["menu_full"];
				$html .="</td>";
				$html .= "<td>";
				$html .= $val."個";
				$html .= "</td>";
				$html .= "</tr>";
			}
			$html .="</tr></tbody></table>";
		}
		echo $html;
	}
	function drawOrderNum(){
		$html ="";
		$ordertxt = "";
		$this->db->countOrtderNumAll($returnArray);
		$html .=  <<<EOM
		<table border="1">
			<tbody>
EOM;
			foreach($returnArray as $key => $val){
				$html .= "<tr><td>";
				$menu = $this->db->getMenu($key);
				$html .= $menu["menu_full"];
				$html .="</td>";
				$html .= "<td>";
				$html .= $val."個";
				$html .= "</td>";
				$html .= "</tr>";
			}
			$html .="</tr></tbody></table>";
		echo $html;
		$this->drawHtmlFooter();
	}
	function drawOrderForm(){
		$txt = $this->getHtmlHeader('order');
		$txt .= <<<EOT
		<div>
		オーダー追加
		<script>
		function is_number(x){
			if( typeof(x) != 'number' && typeof(x) != 'string' ) return false;
    	else		return (x == parseFloat(x) && isFinite(x));
		}
		function addOrder(order){
			document.getElementById("order").value +="m"+order +"o"+ '1';
				countOrder();
		}
		function countOrder(){
				var order = document.getElementById("order").value;
				var arr = [];
				i = 0;
				do{
					if(order[i] == 'm'){
						var orderStr = 0;
						for(++i;i < order.length && is_number(order[i]);i++){
							orderStr *= 10;
							orderStr += order[i];
						}
						orderStr = parseInt(orderStr,10);
						if(order[i] == 'o'){
							var orderNum = 0;
							if(order[++i] == "-"){
								for(++i;i < order.length && is_number(order[i]);i++){
									orderNum = parseInt(orderNum,10);
									orderNum *= 10;
									orderNum -= order[i];
								}
							orderNum = parseInt(orderNum,10);
							}
							else{
								for(i;i < order.length && is_number(order[i]);i++){
									order[i] *= 10;
									orderNum = parseInt(orderNum,10);
									order[i]= parseInt(order[i],10);
									orderNum += order[i];
								}
							orderNum = parseInt(orderNum,10);
							}
						}
					if(!arr[orderStr]){
						arr[orderStr] = 0;
					}
					arr[orderStr] = arr[orderStr]+ orderNum;
				}
			else{
				i++;
			}
		}while(i < order.length);
		for(var key in arr) {
			document.getElementById('order_'+key).innerHTML = arr[key];
		}
				optimizeOrder(arr);
				calcPrice(arr);
	}
	function calcPrice(arr){
				var price = 0;
		for(var key in arr) {
			price += document.getElementById('price_'+key).innerHTML * arr[key];
		}
		document.getElementById('price').innerHTML = price;
	}
	function optimizeOrder(arr){
				document.getElementById('order').value = '';
		for(var key in arr) {
			document.getElementById('order').value += 'm'+key+'o'+arr[key];
		}
	}
		</script>
EOT;
		echo $txt;
		for($i = 0; $i < count($this->menu); $i++){
			$menuID = $this->menu[$i]['id'];
			$fullName = $this->menu[$i]['menu_full'];
			$explain = $this->menu[$i]['explain'];
			$price = $this->menu[$i]['price'];
			echo<<<EOT
			<button onclick="addOrder('$menuID');">
				<table border="1">
					<thead>
						<th>$fullName</th>
					</thead>
					<tbody>
						<tr>
							<td>$explain</td>
							<td rowspan="2" id="order_$menuID">0</td>
						</tr>
						<tr>
							<td id="price_$menuID">$price</td>
						</tr>
					</tbody>
				</table>
			</button>
EOT;
		}
		echo <<<EOT
		<h1>合計<span  id= "price">0</span>円</h1>
		<form method="POST" action="addorder.php">
		<input type="number" name="num"/>
		<input type="text" id="order" name="order"/>
		<input type="submit"/>
		</form>
		</div>
		</body>
		</html>
EOT;
	}
	function drawMenuAll(){
		foreach ($this->menu as $key => $val){
			$id = $val['id'];
			$menu_full = $val['menu_full'];
			$explain = $val['explain'];
			$price = $val['price'];
			echo <<<EOT
			<table border="1">
				<tbody>
					<tr>
						<td>$id</td><td>$menu_full</td>
					</tr>
					<tr>
						<td colspan="2">$explain</td>
					</tr>
					<tr>
						<td colspan="2">$price 円</td>
					</tr>
				</tbody>
			</table>
EOT;
		}
	}

	function makePhpHeader(&$txt){
		$txt = "<?php\n".
				"require('".__FILE__."');\n\n";
	}
	function install(){
		$fileName = ADD_MENU_PHP;
		$txt = <<<EOT
	\$d = new DB();
	\$menuID = \$_POST['id'];
	\$menu_full = \$_POST['menu_full'];
	\$explain = \$_POST['explain'];
	\$price = \$_POST['price'];
	\$d->addMenu(\$menuID, \$menu_full, \$explain, \$price);
	var_dump(\$d->getMenuAll());
	echo	"<a href=\"./\">戻る</a>";
EOT;
		$this->makeFile4PHP($fileName, $txt);
		$fileName = ADD_MENU_HTML;
		$txt = "<form method=\"POST\" action=\"".ADD_MENU_PHP."\">";
		$txt .= <<<EOT
			メニュー番号<br>
		  	<input type="number" name="id"/><br>
			メニュー名<br>
			<input type="text" name="menu_full"/><br>
			説明<br>
			<input type="text" name="explain"/><br>
			価格<br>
			<input type="number" name="price"/><br>
			<input type="submit" value="追加"/>
EOT;
		$this->makeFile4HTML($fileName, $txt);

		$fileName=ADD_ORDER_PHP;
		$txt = <<<EOT
		\$d = new DB();
		\$orderStr = \$_POST['order'];
		if(!(isset(\$orderStr)))die('オーダー追加失敗');
		if(\$err =\$d->addOrderByString(\$orderStr, \$num) != NO_ERROR)echo'失敗'.\$err;
		else header('location:top.php')
		?>
EOT;
		$this->makeFile4PHP($fileName, $txt);

		$fileName = COMPLETE_ORDER_PHP;
		$txt = <<<EOT
\$d = new DB();
\$orderNo = \$_POST['orderNo'];
echo \$d->updateOrderStatusToComplete(\$orderNo);
echo <<<EOM
オーダーを完了しました
<a href="./index.html">トップ</a>
EOM;
?>
EOT;
$this->makeFile4PHP($fileName, $txt);
		$fileName=DEL_ORDER_PHP;
		$txt = <<<EOT
\$d = new DB();
\$orderNo = \$_POST['orderNo'];
echo \$d->deleteOrder(\$orderNo);
echo "オーダーを消しました";

EOT;
$txt .= "echo \"<a href=\\\"".INDEX_PAGE."\\\">トップ</a>\\n;\";";
		$this->makeFile4PHP($fileName, $txt);
		$fileName = INDEX_PAGE;
		$txt = <<<EOT
		<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Insert title here</title>
</head>
<body>
EOT;
		$txt .= "<a href=\"".FORM_ORDER."\">オーダー追加</a><br>".
					"<a href=\"".ADD_MENU_HTML."\">メニュー追加</a><br>".
					"<a href=\"".VIEW_ORDER."\">一覧</a><br>".
					"<a href=\"".QUEUE_ORDER."\">キュー</a><br>".
					"<a href=\"".VIEW_MENU."\">メニュー</a></body></html>";
		$this->makeFile4HTML($fileName, $txt);

		$fileName=VIEW_MENU;
		$txt = <<<EOT
\$d = new DB();
var_dump(\$d->getMenuAll());
EOT;
		$this->makeFile4PHP($fileName, $txt);

		$fileName=DETAIL_ORDER;
		$txt = <<<EOT
\$orderNo = \$_POST['orderNo'];
\$d = new HTML();
htmlspecialchars(DB::deleteSpace(\$orderNo));
\$d->drawHtmlHeader(\$orderNo);
\$d->drawOrder(\$orderNo);
\$d->drawHtmlFooter();
EOT;
		$this->makeFile4PHP($fileName, $txt);

		$fileName=QUEUE_ORDER;
		$txt = <<<EOT
\$d = new HTML();
\$d->drawOrderNum();
EOT;
		$this->makeFile4PHP($fileName, $txt);
		$fileName = FORM_ORDER;
		$txt = <<<EOT
		\$d = new HTML();
		\$d->drawOrderForm();
EOT;
		$this->makeFile4PHP($fileName, $txt);


		$fileName = VIEW_ORDER;
		$txt = <<<EOT
\$d = new HTML();
\$d->drawHtmlHeader("Top");
\$d->drawOrderAll();
EOT;
		$this->makeFile4PHP($fileName, $txt);

		echo <<<EOT
		<a href="./">完了</a>
EOT;
	}
	private function makeFile4PHP($fileName, $txt){
		$fp = fopen($fileName, "w");
		$this->makePhpHeader($t);
		$t .= $txt;
		$t = mb_convert_encoding($t, "UTF-8");
		fwrite($fp, $t);
		fclose($fp);
	}
	private function makeFile4HTML($fileName, $t){
		$fp = fopen($fileName, "w");
		$t = mb_convert_encoding($t, "UTF-8");
		fwrite($fp, $t);
		fclose($fp);
	}
}
$d = new DB();
$d->getOrder(1, $retuenArray);
?>