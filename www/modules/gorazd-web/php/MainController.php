<?php
namespace Gorazd\Virtual;
use \Gorazd\System as Sys;

/**
 * Parent class for the all page controllers. Provides extended functionallity for website controllers.
 * @author mira - niwi (miradrda@volny.cz)
 * @date 3.11.2013
 */
class MainController extends \Gorazd\System\Controller
{
  /** Name of the view class */
  protected $viewClass = '\Gorazd\GorazdWeb\MainView';
  
  # different URL for the form target (downloading SQL)
  public $allowTails = array('gorazd-init.sql'); #same as SQL_FILENAME
  const SQL_FILENAME = 'gorazd-init.sql';
  
  # basic tables to export
  static $TABLES = array(
    'error404', 'TOK',
    'visit', 'visitPage',
    'history', 'historyRecord',
    'website', 'section', 'page', 'menuItem',
    'person', 'user', 'permission',
    'errorLog', 'schedule',# 'mail', 'mailRecipient',
    'personDetail', 'personRelation', 'personSetting', 'personView',
    'articlesCategory', 'article',
    'comment', 'event',
    'version', 'ticket', 'ticketAssignement'
    );
  
  /**
   * Prepares data to display.
   */
  protected final function prepareMainData ()
  {
    $this->addStyle('main');
    $this->displayLeftCol = false;
    $this->displayRightCol = false;
    $this->title = "rr";#TODO
    
    // content replacements
    $this->contentReplacements['sqlInit'] = $this->prepareSqlData();
    $this->contentReplacements['references'] = $this->prepareReferences();
  }
  
  /**
   * Prepares data to display.
   */
  private function prepareSqlData()
  {
    $f = new \Gorazd\FormsBasic\FormBuilder('generate-init', 'SQL init script parameters');
    $f->targetAction = Sys\Env::urlAppend(self::SQL_FILENAME);
    $f->addInput('websiteUrl', 'Your website URL (ex. "mydomain.com")', 128);
    #TODO add HTTPS
    $f->addSubmit('Download SQL script');
    
    if ($f->isSubmitted())
    {
      preg_match('@^(https?://)?(.*)/?$@', $f->val('websiteUrl'), $matches);  # always matches
      $sql = $this->getSql($matches[2]);
      if ($sql !== false)
      {
        header('Content-type: text/sql');
        exit($sql);
      }
    }
    else if (Sys\Env::isActualTail(self::SQL_FILENAME))
      Sys\Env::redirect(Sys\Env::$urlWithoutTail);
    
    return $f;
  }
  
  /**
   *
   */
  private function prepareReferences ()
  {
    $webs = array(
        'www.iklubovna.cz' => array('iklubovna', 'Children community website'),
        'www.niwi.cz' => array('niwi', 'Author\'s personal website'),
        'www.spjf.cz' => array('spjf', 'Children organisation'),
        'www.dlouhodobka.cz' => array('dlouhodobka', 'Long-term games for children teams'),
        'obchod.spjf.cz' => array('obchod', 'SPJF organisation e-shop')
    );
    $out = '';
    foreach ($webs as $link => $data)
    {
      $out .= <<<EOT
      <a href="http://$link" class="reference-box">
        <img src="/images/gorazd-web/references/{$data[0]}.jpg"><br>{$data[1]}
      </a>
EOT;
    }
    return '<p>Which websites runs on Gorazd System?</p>' . $out . '<p>... and several smaller websites.</p>';
  }
  
  
  /**
   *
   */
  private function getSql ($websiteUrl)
  {
    $thisPage = Sys\Env::$urlWithoutTail;
    $now = Sys\Utils::now();
    $sql = <<<EOT
--
-- SQL dump of required tables for Gorazd system
-- Generated by script on the page $thisPage
-- Date: $now
--

SET AUTOCOMMIT = 0;
START TRANSACTION;\n\n
EOT;
    
    # dump table structures
    $dbname = conf('dbDatabase');
    foreach (self::$TABLES as $table)
    {
      $type = $table == 'personView' ? 'VIEW' : 'TABLE';
      $res = Sys\Db::query("SHOW CREATE $type `$dbname`.`$table`");
      if (!$res)
        return err('Interní chyba při práci s daty', 2, 'Chyba při zjišťování struktury tabulky '.$table);
      $row = $res->fetch_row();
      if ($row)
      {
        # throw off auto increment
        $struct = preg_replace('/AUTO_INCREMENT=\d+/', 'AUTO_INCREMENT=1', $row[1]);
        // throw off superadmin params in view
        if ($table == 'personView')
        {
          $struct = preg_replace('$ALGORITHM=.*SECURITY DEFINER$', '', $struct);
        }
        $sql .= <<<EOT
-- ----------------------------------------
-- Structure of the table $table
-- ----------------------------------------
$struct;\n\n
EOT;
      }
      else
        msg('Nepodařilo se načíst informace o tabulce '.$table, MSG_ERROR);
    }
    
    
    # add TOK values
    $sql .= <<<EOT
-- ----------------------------------------
-- Data from table TOK
-- ----------------------------------------
INSERT INTO `TOK` (`key`, `value`, `type`) VALUES\n
EOT;
    $res = Sys\Db::query("SELECT * FROM TOK WHERE `key` < 1000 ORDER BY `key`");
    $tokStrings = array();
    while ($row = $res->fetch_assoc())
      $tokStrings[] = "(". esc($row['key']) .", '". esc($row['value']) ."', '". esc($row['type']) ."')";
    $sql .= implode(",\n", $tokStrings) . ";\n\n";

    
    # insert other values
    $sql .= <<<EOT
-- add website
INSERT INTO `website` (`id`, `name`, `title`)
  VALUES (1, '$websiteUrl', '$websiteUrl');

-- add the main page
INSERT INTO `page` (`id`, `title`, `content`, `controller`, `readPermission`, `writePermission`)
  VALUES (1,'Úvodní strana', '<p>Vítejte na úvodní stránce webu!</p>', 'WebAdmin\InitController', 0, 5);
INSERT INTO `menuItem` (`pageId`, `websiteId`, `url`, `rank`)
  VALUES (1, 1, '', 1);

-- add MenuController
INSERT INTO `page` (`id`, `title`, `content`, `controller`, `readPermission`, `writePermission`)
   VALUES (2, 'Struktura webu', 'Pomocí struktury menu níže je možné upravovat jednotlivé stránky a menu.', 'WebAdmin\MenuController', 5, 5);
INSERT INTO `menuItem` (`pageId`, `websiteId`, `url`, `rank`)
  VALUES (2, 1, 'web-structure', 1);

EOT;
    
    return $sql;
  }



  /**
   * Zobrazi formular pro prihlaseni.
   */
  protected function prepareLoginBox ()
  {
    $out = '';
    if (!Sys\Env::$user->isLogged())
    {
      $wc = $this->getViewClass();
      $out .= call_user_func(array($wc, 'printLoginDialog'));
    }
    else
    {
      $out .= "\n      <div id=\"login\" class=\"loginbox\">\n";
      $out .= "Přihlášen". Sys\Env::$user->sexTermination .": \n" . Sys\Env::$user->adminType . "\n";
      $out .= Sys\Env::$user->label() . "\n";
      $out .= ' <form action="'.ROOT_URL.'" method="post"><input type="submit" name="gs_logOut" value="Odhlásit" class="button" /></form>'."\n";
      $out .= "      </div>\n";
    }
    $this->loginBoxString = $out;
  }
}

?>
