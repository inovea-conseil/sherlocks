<?php
/* Copyright (C) 2012      Mikael Carlavan        <contact@mika-carl.fr>
 *                                                http://www.mikael-carlavan.fr
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/sherlocks/class/sherlocks.class.php
 *      \ingroup    ndfp
 *      \brief      File of class to manage transactions
 */

require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");

class Sherlocks extends CommonObject
{
	var $db;
	var $error;
	
	var $element = 'sherlocks';
	var $table_element = 'sherlocks';
	var $table_element_line = '';
	var $fk_element = 'fk_sherlocks';
	var $ismultientitymanaged = 0;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

	

	var $id;
	var $ref;
	var $key;
	var $entity;
	var $datec;			
	var $type;
	var $fk_object;	
	
	
   /**
	*  \brief  Constructeur de la classe
	*  @param  DB          handler acces base de donnees
	*/
	function __construct($db)
	{
		$this->db = $db;
	}

	function copyBinFiles($path, $version = 'lin32')
	{
		global $conf, $langs;

		$error = false;
		$isWin = strpos($version, 'lin') === false ? true : false;
		$extension = $isWin ? '.exe' : '';

		$destFile = $path .DIRECTORY_SEPARATOR.($isWin ? 'response.exe' : 'response');
		$srcFile = dol_buildpath('/sherlocks/bin/response_' .$version. $extension);
		
		if (copy($srcFile, $destFile))
		{
			@chmod($destFile, 0755);
			
			$this->error = $langs->trans("SetupSaved");
			$error = false;			
		}
		else
		{
			$this->error = $langs->trans("ErrorDuringScriptCopy");
			$error = true;
		}

		$destFile = $path .DIRECTORY_SEPARATOR.($isWin ? 'request.exe' : 'request');
		$srcFile = dol_buildpath('/sherlocks/bin/request_' .$version. $extension);
		
		if (copy($srcFile, $destFile))
		{
			@chmod($destFile, 0755);
			
			$this->error = $langs->trans("SetupSaved");
			$error = false;			
		}
		else
		{
			$this->error = $langs->trans("ErrorDuringScriptCopy");
			$error = true;
		}	

		$destFile = $path .DIRECTORY_SEPARATOR.'parmcom.sherlocks';
		$srcFile = dol_buildpath('/sherlocks/bin/parmcom.sherlocks');
		
		if (copy($srcFile, $destFile))
		{
			@chmod($destFile, 0644);
			
			$this->error = $langs->trans("SetupSaved");
			$error = false;			
		}
		else
		{
			$this->error = $langs->trans("ErrorDuringScriptCopy");
			$error = true;
		}	

		$destFile = $path .DIRECTORY_SEPARATOR.'certif.fr.014295303911111.php';
		$srcFile = dol_buildpath('/sherlocks/bin/certif.fr.014295303911111.php');
		
		if (copy($srcFile, $destFile))
		{
			@chmod($destFile, 0644);
			
			$this->error = $langs->trans("SetupSaved");
			$error = false;			
		}
		else
		{
			$this->error = $langs->trans("ErrorDuringScriptCopy");
			$error = true;
		}	

		return $error ? -1 : 1;	

	}

	function createConfigFiles($path)
	{
		global $conf, $langs, $mysoc;

		$error = false;

		$id = $conf->global->API_TEST ? '014295303911111' : $conf->global->API_ID;
		$key = $conf->global->API_KEY ? $conf->global->API_KEY : '';

		$urllogo = '';
		$rootfordata = str_replace("documents", "datas", DOL_DATA_ROOT);

		if (! empty($mysoc->logo) && is_readable($rootfordata.'/logos/'.$mysoc->logo))
		{
			$urllogo = DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=companylogo&amp;file='.urlencode($mysoc->logo);
		}

		// Write parcom file
		$destFile = $path .DIRECTORY_SEPARATOR.'parmcom.'.$id;

		$sasprint_url = str_replace('/htdocs', '/', DOL_MAIN_URL_ROOT);

		$handleFichier = @fopen($destFile, "w+");
		@fwrite($handleFichier, "#\r\n");
		@fwrite($handleFichier, "# URL de retour automatique de la reponse du paiement\r\n\n");
		@fwrite($handleFichier, "AUTO_RESPONSE_URL!".dol_buildpath('/sherlocks/confirm.php', 2)."!\r\n\n");
		@fwrite($handleFichier, "# URL de retour suite a paiement refuse\r\n\n");
		@fwrite($handleFichier, "CANCEL_URL!".dol_buildpath('/sherlocks/error.php', 2)."!\r\n\n");
		@fwrite($handleFichier, "# URL de retour suite a paiement accepte\r\n\n");
		@fwrite($handleFichier, "RETURN_URL!".dol_buildpath('/sherlocks/success.php', 2)."!\r\n\n");
		@fwrite($handleFichier, "# Code devise  (978=EURO)\r\n\n");
		@fwrite($handleFichier, "CURRENCY!978!\r\n\n");
		@fwrite($handleFichier, "# Logo du commercant\r\n\n");
		@fwrite($handleFichier, "LOGO2!!\r\n\n");
		@fwrite($handleFichier, "# Liste des cartes acceptÈes par le commercant\r\n\n");
		@fwrite($handleFichier, "PAYMENT_MEANS!CB,2,VISA,2,MASTERCARD,2!\r\n\n");
		@fwrite($handleFichier, "# END OF FILE\r\n\n");
		@fclose($handleFichier);

		// Write path file
		$destFile = $path .DIRECTORY_SEPARATOR.'pathfile';

		$handleFichier = @fopen($destFile, "w+");
		@fwrite($handleFichier, "#\r\n");
		@fwrite($handleFichier, "# Activation (YES) / Désactivation (NO) du mode DEBUG\r\n\n");
		@fwrite($handleFichier, "DEBUG!NO!\r\n\n");
		@fwrite($handleFichier, "# Chemin vers le répertoire des logos depuis le web alias  \r\n\n");
		@fwrite($handleFichier, "D_LOGO!".dol_buildpath('/sherlocks/img/', 1)."!\r\n\n");
		@fwrite($handleFichier, "# Fichier des  paramètres sherlocks\r\n\n");
		@fwrite($handleFichier, "F_DEFAULT!".$path .DIRECTORY_SEPARATOR."parmcom.sherlocks!\r\n\n");
		@fwrite($handleFichier, "# Fichier paramètre commercant\r\n\n");
		@fwrite($handleFichier, "F_PARAM!".$path .DIRECTORY_SEPARATOR."parmcom!\r\n\n");
		@fwrite($handleFichier, "# Certificat du commercant\r\n\n");
		@fwrite($handleFichier, "F_CERTIFICATE!".$path .DIRECTORY_SEPARATOR."certif!\r\n\n");
		@fwrite($handleFichier, "# Type du certificat \r\n\n");
		@fwrite($handleFichier, "F_CTYPE!php!\r\n\n");
		@fwrite($handleFichier, "# END OF FILE\r\n\n");
		@fclose($handleFichier);

		// Write certif file
		$destFile = $path .DIRECTORY_SEPARATOR.'certif.fr.'.$conf->global->API_ID.'.php';

		$handleFichier = @fopen($destFile, "w+");
		@fwrite($handleFichier, $key);
		@fclose($handleFichier);

		return $error ? -1 : 1;	

	}

	/**
	 * Fetch object from database
	 *
	 * @param 	id	    Id of the payment
     * @param 	key  	Key of the payment
	 * @return 	int		<0 if KO, >0 if OK
	 */
	function fetch($id, $key = '')
	{
	    global $conf, $langs;

        if (!$id && empty($key))
        {
			return -1;
        }
        
        $sql = "SELECT c.rowid, c.ref, c.key, c.type, c.fk_object, c.datec";
        $sql.= " FROM ".MAIN_DB_PREFIX."sherlocks as c";
        $sql.= " WHERE c.entity = ".$conf->entity;
    
        if ($id)   $sql.= " AND c.rowid = ".$id;
        if ($key)  $sql.= " AND c.key = '".$this->db->escape($key)."'";

		dol_syslog("Sherlocks::fetch sql=".$sql, LOG_DEBUG);

		$result = $this->db->query($sql);
		if ($result > 0)
		{
			$num = $this->db->num_rows($result);
			
			if ($num)
			{
				$obj = $this->db->fetch_object($result);

				$this->id                = $obj->rowid;
				$this->ref               = $obj->ref;
				$this->key               = $obj->key;
				$this->entity            = $obj->entity;
				$this->datec             = $this->db->jdate($obj->datec);			
				$this->type    			= trim($obj->type);
				$this->fk_object    	= $obj->fk_object;

				return $this->id;
            }
            else
            {
            	return 0;
            }
		}
		else
		{
			$this->error = $this->db->error()." sql=".$sql;
			return -1;
		}
	}
	
	
	/**
	 * Create object in database
	 *
	 * @param 	$user	User that creates
	 * @return 	int		<0 if KO, >0 if OK
	 */
	function create($user)
	{
		global $conf, $langs;


        $this->datec = dol_now();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."sherlocks (";
        $sql.= "`ref`";
        $sql.= ", `entity`";
        $sql.= ", `datec`";
        $sql.= ", `key`";
        $sql.= ", `type`";
		$sql.= ", `fk_object`";
        $sql.= ") ";
        $sql.= " VALUES (";
		$sql.= " ".($this->ref ? "'".$this->db->escape($this->ref)."'" : "''");
		$sql.= ", ".$conf->entity." ";
        $sql.= ", '".$this->db->idate($this->datec)."'";
        $sql.= ", ".($this->key ? "'".$this->db->escape($this->key)."'" : "''");
        $sql.= ", ".($this->type ? "'".$this->db->escape($this->type)."'" : "''");
		$sql.= ", ".($this->fk_object ? $this->fk_object : 0);
		$sql.= ")";

		dol_syslog("Sherlocks::create sql=".$sql, LOG_DEBUG);

		$result = $this->db->query($sql);

		if ($result)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."sherlocks");
			return $this->id;
		}
		else
		{
			$this->error = $this->db->error()." sql=".$sql;
			return -1;
		}

	}

	function getPaymentResponse()
 	{

		global $db, $conf, $langs, $mysoc, $user;

		$error = false;
		

		$path = rtrim($conf->global->API_CGI, "\\/");
		$path = str_replace('/', DIRECTORY_SEPARATOR, $path);

		$id = $conf->global->API_TEST ? '014295303911111' : $conf->global->API_ID;

		$listeParametres = array(
			'message' => $_POST['DATA'],
			'pathfile' => $path .DIRECTORY_SEPARATOR.'pathfile'
		);

		$parametres = '';
		foreach ($listeParametres as $key => $value)
		{
			$parametres.= ($key.'='.$value.' ');
		}
		$parametres = escapeshellcmd($parametres);	

		//dol_syslog(get_class($this)."::getPaymentResponse() parametres=".$parametres);

		$result = exec($path."/response ".$parametres);

 		//dol_syslog(get_class($this)."::getPaymentResponse() result=".$result);
 		

		$tableauResultat = explode ("!", $result);

		$code = $tableauResultat[1];
		//dol_syslog(get_class($this)."::getPaymentResponse() code=".$code);
		$error = $code != 0 ? true : false;

		//dol_syslog(get_class($this)."::getPaymentResponse() error=".intval($error));

		$idMarchant = $tableauResultat[3];
		$montantTransaction = floatval($tableauResultat[5]);
		$idTransaction = $tableauResultat[6];
		$dateTransaction = $tableauResultat[8];
		$codeRetour = $tableauResultat[11];
		$idAuthorisation = $tableauResultat[13];
		$codeRetourBanque = $tableauResultat[18];
		$idClient = $tableauResultat[26];
		$refCommande = $tableauResultat[27];
		$itemKey = $tableauResultat[32];
		/*if ($id != $idMarchant)
		{
			$error = true;
		}*/

		//dol_syslog(get_class($this)."::getPaymentResponse() error=".intval($error));
		//dol_syslog(get_class($this)."::getPaymentResponse() id=".$id);
		//dol_syslog(get_class($this)."::getPaymentResponse() idMarchant=".$idMarchant);

		//dol_syslog(get_class($this)."::getPaymentResponse() error=".intval($error));
		//dol_syslog(get_class($this)."::getPaymentResponse() result=".$result);
		/*
		if ($montantTransaction != strval(100.0 * $commande->total_ttc))
		{
			$error = true;
		}
		*/
		//dol_syslog(get_class($this)."::getPaymentResponse() error=".intval($error));
		//dol_syslog(get_class($this)."::getPaymentResponse() calcul=".(100*price2num($commande->total_ttc)));
		//dol_syslog(get_class($this)."::getPaymentResponse() montantTransaction=".$montantTransaction);
		//dol_syslog(get_class($this)."::getPaymentResponse() test=".intval($montantTransaction != (100*price2num($commande->total_ttc))));

		
		$success = ($codeRetour == '00' && !$error) ? true : false;
		return array($success, $itemKey, $idAuthorisation, $idTransaction, $montantTransaction);
 	}
 		 		
	function getPaymentRequest()
 	{

		global $db, $conf, $langs, $mysoc, $user;

		$error = false;

		$isInvoice = ($this->type == 'invoice' ? true : false);
		$item = ($isInvoice) ? new Facture($this->db) : new Commande($this->db);
		$result = $item->fetch($this->fk_object);
			
		$alreadyPaid = 0;
		$creditnotes = 0;
		$deposits = 0;
		$totalObject = 0;
		$amountTransaction = 0;
	
		$result = $item->fetch_thirdparty();
	
		if ($isInvoice)
		{
			$alreadyPaid = $item->getSommePaiement();
			$creditnotes = $item->getSumCreditNotesUsed();
			$deposits = $item->getSumDepositsUsed();         
		}

	
		$totalObject = $item->total_ttc;
	   
		$alreadyPaid = empty($alreadyPaid) ? 0 : $alreadyPaid;
		$creditnotes = empty($creditnotes) ? 0 : $creditnotes;
		$deposits = empty($deposits) ? 0 : $deposits;
		$totalObject = empty($totalObject) ? 0 : $totalObject;
	
		$amountTransaction =  $totalObject - ($alreadyPaid + $creditnotes + $deposits);

		$path = rtrim($conf->global->API_CGI, "\\/");
		$path = str_replace('/', DIRECTORY_SEPARATOR, $path);

		$listeParametres = array(
			'merchant_id' => $conf->global->API_TEST ? '014295303911111' : $conf->global->API_ID,
			'merchant_country' => 'fr',
			'amount' => 100.0 * $amountTransaction,
			'currency_code' => 978,
			'pathfile' => $path .DIRECTORY_SEPARATOR.'pathfile',
			'customer_id' => $user->id,
			'normal_return_url' => dol_buildpath('/sherlocks/success.php', 2),
			'cancel_return_url' => dol_buildpath('/sherlocks/return.php', 2),
			'automatic_response_url' => dol_buildpath('/sherlocks/confirm.php', 2),
			//'customer_email' => $user->email ? $user->email : '',
			//'customer_name' => $user->lastname ? $user->lastname : '',
			//'customer_firstname' => $user->firstname ? $user->firstname : '',
			'order_id' => $item->ref,
			'data' => $this->key
		);

		$parametres = '';
		foreach ($listeParametres as $key => $value)
		{
			$parametres.= ($key.'='.$value.' ');
		}
		$parametres = escapeshellcmd($parametres);	
		//dol_syslog(get_class($this)."::getPaymentRequest() parametres=".$parametres);
		
		$result = exec($path."/request ".$parametres);		
		//dol_syslog(get_class($this)."::getPaymentRequest() result=".$result);
		
		$tableauResultat = explode ("!", $result);
		
		$codeRetour = $tableauResultat[1];
		$error = ($tableauResultat[1] != 0 ? true : false);
		$codeHtml = $error ? $tableauResultat[2] : $tableauResultat[3];
		
		return $codeHtml;
 	}	 
}

?>
