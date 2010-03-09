<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */
/**
* Class ilPaymentPrices
* 
* @author Stefan Meyer <meyer@leifos.com>
* @version $Id: class.ilPaymentPrices.php 22133 2009-10-16 08:09:11Z nkrzywon $
*
* @package ilias-core
*/
class ilPaymentPrices
{
	private $ilDB;

	private $pobject_id;

	private $price;
	private $currency;
	private $duration;
	private $unlimited_duration = 0;
	
	// TODO later -> this is for using different currencies 
	private $currency_conversion_rate = 1;

	private $prices = array();
	
	public function ilPaymentPrices($a_pobject_id = 0)
	{
		global $ilDB;

		$this->db = $ilDB;

		$this->pobject_id = $a_pobject_id;

		$this->__read();
	}

	// SET GET
	public function getPobjectId()
	{
		return $this->pobject_id;
	}

	public function getPrices()
	{
		return $this->prices ? $this->prices : array();
	}
	function getPrice($a_price_id)
	{
		return $this->prices[$a_price_id] ? $this->prices[$a_price_id] : array();
	}

	
	// STATIC
	public static function _getPrice($a_price_id)
	{
		global $ilDB, $ilSettings;
		
		$res = $ilDB->queryf('
			SELECT * FROM payment_prices 
			WHERE price_id = %s',
			array('integer'), array($a_price_id));
		
		while($row = $ilDB->fetchObject($res))
		{
			$price['duration'] = $row->duration;
			$price['unlimited_duration'] = $row->unlimited_duration;
			$price['currency'] = $row->currency;
			$price['price'] = $row->price;

		}	
		return count($price) ? $price : array();
	}

	public static function _countPrices($a_pobject_id)
	{
		global $ilDB;		
	
		$res = $ilDB->queryf('
			SELECT count(price_id) FROM payment_prices 
			WHERE pobject_id = %s',
			array('integer'),
			array($a_pobject_id));
				
		$row = $res->fetchRow(DB_FETCHMODE_ARRAY);

		return ($row[0]);
	}

	public static function _getPriceString($a_price_id)
	{
		#include_once './Services/Payment/classes/class.ilPaymentCurrency.php';
		
		global $lng;
		
		$price = ilPaymentPrices::_getPrice($a_price_id);

		return (float)$price['price'];
		
	}
	
	public static function _formatPriceToString($a_price)
	{
		include_once './Services/Payment/classes/class.ilGeneralSettings.php';
		
		$genSet = new ilGeneralSettings();
		$currency_unit = $genSet->get('currency_unit');

		return $a_price . ' ' . $currency_unit;
		
/* TODO: after currency implementation is finished	-> replace whole function
 * 		include_once './Services/Payment/classes/class.ilPaymentCurrency.php';

		$separator= ilPaymentCurrency::_getDecimalSeparator();
		$currency_symbol = ilPaymentCurrency::_getSymbol($a_currency_id);
		$price_string = number_format($a_price,'2',$separator,'');
		
		return $price_string . ' ' . $currency_symbol;
 */	
	}
	

	public static function _getPriceStringFromAmount($a_price)
	{
		include_once './Services/Payment/classes/class.ilPaymentCurrency.php';
		include_once './Services/Payment/classes/class.ilGeneralSettings.php';

		global $lng;

		$genSet = new ilGeneralSettings();
		$currency_unit = $genSet->get("currency_unit");

		$pr_str = '';		

		$pr_str = number_format($a_price , 2, ",", ".");
/* TODO: CURRENCY 	$pr_str = number_format($a_price * $this->getCurrencyConversionRate() , 2, ",", ".");
 * 		remove genset
 * */
 		
		return $pr_str . " " . $currency_unit;		
	}
	
		
	public static function _getTotalAmount($a_price_ids)
	{

		include_once './Services/Payment/classes/class.ilPaymentPrices.php';
#		include_once './Services/Payment/classes/class.ilPaymentCurrency.php';
		include_once './Services/Payment/classes/class.ilGeneralSettings.php';

		global $ilDB, $lng;

		$genSet = new ilGeneralSettings();
		$currency_unit = $genSet->get("currency_unit");

		$amount = array();

		if (is_array($a_price_ids))
		{
			for ($i = 0; $i < count($a_price_ids); $i++)
			{
				$price_data = ilPaymentPrices::_getPrice($a_price_ids[$i]["id"]);

				$price = (float) $price_data["price"];
				$amount[$a_price_ids[$i]["pay_method"]] += (float) $price;
			}
		}
/* TODO: CURRENCY  replace 'if'  & remove genset
 		if (is_array($a_price_ids))
		{				
			$default_currency =  ilPaymentCurrency::_getDefaultcurrency();
			
			for ($i = 0; $i < count($a_price_ids); $i++)
			{
				$price_data = ilPaymentPrices::_getPrice($a_price_ids[$i]["id"]);

				if($price_data['currency'] != $default_currency['currency_id'])
				{
					$conversion_rate = ilPaymentCurrency::_getConversionRate($price_data['currency']);
					$price = round(((float) $price_data['price'] * $conversion_rate),2);
				}
				else
				$price = (float) $price_data["price"];
				
				$amount[$a_price_ids[$i]["pay_method"]] += (float) $price;
			}
		} 
 */
		return $amount;
	}
		

	public function setPrice($a_price = 0)
	{
		$this->price = preg_replace('/^0+/','',$a_price);

		$this->price = $a_price;
	}

	public function setCurrency($a_currency_id)
	{
		$this->currency = $a_currency_id;
	}
	public function setDuration($a_duration)
	{
		if($this->unlimited_duration == '1' && ($a_duration == '' || null)) 
		$a_duration = 0;
		
		$this->duration = (int)$a_duration;
	}
	
	public function setUnlimitedDuration($a_unlimited_duration)
	{
		if($a_unlimited_duration) 
			$this->unlimited_duration = (int)$a_unlimited_duration;
		else
			$this->unlimited_duration = 0;
	}
	
	public function add()
	{
		$next_id = $this->db->nextId('payment_prices');
		
		$res = $this->db->manipulateF('
			INSERT INTO payment_prices 
			(	price_id,
				pobject_id,
				currency,
				duration,
				unlimited_duration,
				price
				)
			VALUES (%s, %s, %s, %s, %s, %s)',

			array('integer','integer', 'integer', 'integer', 'integer', 'float'),
			array(	$next_id,
					$this->getPobjectId(),
					$this->__getCurrency(),
					$this->__getDuration(),
					$this->__getUnlimitedDuration(),
					$this->__getPrice()
		));
		
		$this->__read();
		
		return true;
	}
	public function update($a_price_id)
	{
		$res = $this->db->manipulateF('
			UPDATE payment_prices SET
			currency = %s,
			duration = %s,
			unlimited_duration = %s,
			price = %s			
			WHERE price_id = %s',

			array('integer', 'integer','integer', 'float', 'integer'),
			array(	$this->__getCurrency(),
					$this->__getDuration(),
					$this->__getUnlimitedDuration(),
					$this->__getPrice(),
					$a_price_id
		));

		$this->__read();

		return true;
	}
	public function delete($a_price_id)
	{
		$statement = $this->db->manipulateF('
			DELETE FROM payment_prices
			WHERE price_id = %s',
			array('integer'), array($a_price_id));

		$this->__read();

		return true;
	}
	public function deleteAllPrices()
	{
		$statement = $this->db->manipulateF('
			DELETE FROM payment_prices
			WHERE pobject_id = %s',
			array('integer'),
			array($this->getPobjectId()));
		
		$this->__read();

		return true;
	}

	public function validate()
	{	
		
		$duration_valid = false;
		$price_valid = false; 
		
		if(preg_match('/^(([1-9][0-9]{0,1})|[0])?$/',$this->__getDuration())	
		|| ((int)$this->__getDuration() == 0 && $this->__getUnlimitedDuration() == 1))
		{
			$duration_valid = true;
		}

		if(preg_match('/[0-9]/',$this->__getPrice()))
		{
			
			$price_valid = true;
		}
			
	if($duration_valid == true && $price_valid == true)
	{
		return true;
		
	}

	else return false;
	
	}
	// STATIC
	public static function _priceExists($a_price_id,$a_pobject_id)
	{
		global $ilDB;

		$res = $ilDB->queryf('
			SELECT * FROM payment_prices
			WHERE price_id = %s
			AND pobject_id = %s',
			array('integer', 'integer'),
			array($a_price_id, $a_pobject_id));
		
		return $res->numRows() ? true : false;
	}
				  
	// PRIVATE

	private function __getPrice()
	{
		return $this->price;
	}	
	private function __getCurrency()
	{
		/*TODO: CURRENCY  not finished yet -> return 1 as default */
		if($this->currency == null)
		$this->currency = 1;
		return $this->currency;
	}
	private function __getDuration()
	{
		return $this->duration;
	}
	private function __getUnlimitedDuration()
	{
		return $this->unlimited_duration;
	}

	private function __read()
	{
		$this->prices = array();

		$res = $this->db->queryf('
			SELECT * FROM payment_prices
			WHERE pobject_id = %s
			ORDER BY duration', 
		array('integer'),
		array($this->getPobjectId()));
		
				
		while($row = $this->db->fetchObject($res))
		{
			$this->prices[$row->price_id]['pobject_id'] = $row->pobject_id;
			$this->prices[$row->price_id]['price_id'] = $row->price_id;
			$this->prices[$row->price_id]['currency'] = $row->currency;
			$this->prices[$row->price_id]['duration'] = $row->duration;
			$this->prices[$row->price_id]['unlimited_duration'] = $row->unlimited_duration;
			$this->prices[$row->price_id]['price'] = $row->price;
//TODO: CURRENCY $this->prices[$row->price_id]['price'] = $row->price * $this->getCurrencyConversionRate();			
		}
	}
	
	public function getNumberOfPrices()
	{
		return count($this->prices);
	}
	
	public function getLowestPrice()
	{				
		$lowest_price_id = 0;
		$lowest_price = 0;

		foreach ($this->prices as $price_id => $data)
		{
			$current_price = $data['price'];

			if($lowest_price  == 0|| 
			   $lowest_price > (float)$current_price)
			{
				$lowest_price = (float)$current_price;
				$lowest_price_id = $price_id;
			}
		}
		
		return is_array($this->prices[$lowest_price_id]) ? $this->prices[$lowest_price_id] : array();
	}
}
?>
