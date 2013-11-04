<?php
namespace Gorazd\GorazdWeb;
use \Gorazd\System as Sys;

/**
 * Parent class for the all page templates (views).
 * @author mira - niwi (miradrda@volny.cz)
 * @date 3.11.2013
 */
class MainView extends \Gorazd\System\View
{
  /**
   * Display page top part
   */
  protected function displayTop ()
  {
    $root = ROOT_URL;
    echo <<<EOT
    <div id="top">
      <img src="/images/gorazd-web/top.jpg">
      <div id="top-box">
        <h1>Gorazd system</h1>
        <strong>Open-source PHP framework and <abbr title="Content Management System">CMS</abbr></strong>
      </div>
      <a href="#about" class="links" id="link-about">About</a>
      <a href="#installation" class="links" id="link-install">How to install</a>
      <a href="#references" class="links" id="link-references">References</a>
      <a href="#download" class="links" id="link-download">Download</a>
    </div>
EOT;
  }



  /**
   * Simple basic function for displaying the content of the page.
   */
  protected function displayHeading()
  {
    ;
  }
  
  
  
  /**
   * Dispalys the page footer.
   */
  protected function displayFooter()
  {  
    ?>
    <div id="footer">
      <div class="copyright left">&copy; <a href="http://www.niwi.cz">niwi</a> <?php echo conf('copyrightYear');?> | <a href="http://gorazd.niwi.cz" title="Redakční systém Gorazd">RS Gorazd</a></div>
    <?php
    $this->displayFooterAdmin();
  ?>
      <br class="clear invisible" />
    </div>
<?php
  }
}
?>
