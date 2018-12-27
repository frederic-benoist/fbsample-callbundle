<?php
/**
 * 2007-2018 Frédéric BENOIST
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 *  @author    Frédéric BENOIST
 *  @copyright 2013-2018 Frédéric BENOIST <https://www.fbenoist.com/>
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
     exit;
}

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Yaml\Yaml;

class FbSample_CallBundle extends Module
{
    public function __construct()
    {
        $this->name = 'fbsample_callbundle';
        $this->version = '1.7.0';
        $this->author = 'Frederic BENOIST';
        $this->tab = 'others';
        $this->bootstrap = true;

        parent::__construct();
        $this->displayName = $this->l('Sample module');
        $this->description = $this->l('Call Symfony Bundle sample module (PrestaShop 1.7)');
    }

    public function install()
    {
        return parent::install()
            && $this->registerBundle(
                _PS_ROOT_DIR_.'/app/AppKernel.php',
                'TrainingBundle/TrainingBundle'
            )
            && $this->addRouting(
                'training',
                '@TrainingBundle/Resources/config/routing.yml'
            )
            && $this->registerHook('displayAdminNavBarBeforeEnd')
            && $this->registerHook('displayBackOfficeHeader');
    }

    public function uninstall()
    {
        return
            $this->unregisterBundle(
                _PS_ROOT_DIR_.'/app/AppKernel.php',
                'TrainingBundle/TrainingBundle'
            )
            && $this->removeRouting(
                'training',
                '@TrainingBundle/Resources/config/routing.yml'
            )
            && parent::uninstall();
    }


    /**
     * Register bundle in AppKernel
     *
     * @param string $appKernelFileAndPath appKernel file name and path
     * @param string $bundle bundle namespace and name (use / between names)
     * @return boolean true if success
     */
    public function registerBundle($appKernelFileAndPath, $bundle)
    {
        $bundle = str_replace("/", "\\", $bundle);
        $newBundle = "new {$bundle}(),";

        $appContent = Tools::file_get_contents($appKernelFileAndPath);
        $pattern = '/\$bundles\s?=\s?array\((.*?)\);/is';
        preg_match($pattern, $appContent, $matches);

        $bList = rtrim($matches[1], "\n ");
        $e = explode(",", $bList);
        $firstBundle = array_shift($e);
        $tabs = substr_count($firstBundle, '    ');

        $newBList = "\$bundles = array("
            .$bList."\n"
            .str_repeat('    ', $tabs).$newBundle."\n"
            .str_repeat('    ', $tabs-1).");";

        file_put_contents($appKernelFileAndPath, preg_replace($pattern, $newBList, $appContent));
        return true;
    }

    /**
     * unRegister bundle in AppKernel
     *
     * @param string $appKernelFileAndPath appKernel file name and path
     * @param string $bundle bundle namespace and name (use / between names)
     * @return boolean true if success
     */
    public function unregisterBundle($appKernelFileAndPath, $bundle)
    {
        $bundle = str_replace("/", "\\", $bundle);

        $appContent = Tools::file_get_contents($appKernelFileAndPath);
        $pattern = '/\$bundles\s?=\s?array\((.*?)\);/is';
        preg_match($pattern, $appContent, $matches);

        $bList = rtrim($matches[1], "\n ");
        $e = explode(",", $bList);
        $firstBundle = array_shift($e);
        $tabs = substr_count($firstBundle, '    ');

        $arrBundle = explode("\n", $bList);
        $newBList = '$bundles = array(';
        foreach ($arrBundle as $oneBundle) {
            if (false === strpos($oneBundle, $bundle)) {
                $newBList .= $oneBundle."\n";
            }
        }
        $newBList .=str_repeat('    ', $tabs-1).");";
        file_put_contents($appKernelFileAndPath, preg_replace($pattern, $newBList, $appContent));
        return true;
    }

    /**
     * Add routing in app/config/routing.yml
     *
     * @param string $routeRcName resource name
     * @param string $routeRcPath resource path
     * @return void
     */
    public function addRouting($routeRcName, $routeRcPath)
    {
        $routingCfgFileAndPath = _PS_ROOT_DIR_.'/app/config/routing.yml';
        $currentValue = Yaml::parseFile($routingCfgFileAndPath);
        if (!isset($currentValue[$routeRcName])) {
            $appContent = Tools::file_get_contents($routingCfgFileAndPath);
            $appContent = rtrim($appContent, "\n")."\n";
            $appContent .= "\n"
                    .$routeRcName.':'."\n"
                    .'    resource: "'.$routeRcPath.'"'."\n";
            file_put_contents($routingCfgFileAndPath, $appContent);
        }
        return true;
    }

    /**
     * Remove routing in app/config/routing.yml
     *
     * @param string $routeRcName resource name
     * @param string $routeRcPath resource path
     * @return void
     */
    public function removeRouting($routeRcName, $routeRcPath)
    {
        $routingCfgFileAndPath = _PS_ROOT_DIR_.'/app/config/routing.yml';
        $currentValue = Yaml::parseFile($routingCfgFileAndPath);
        if (isset($currentValue[$routeRcName])) {
            $appContent = Tools::file_get_contents($routingCfgFileAndPath);
            $cfgLines = explode("\n", $appContent);
            $newContent = '';
            foreach ($cfgLines as $cfgLine) {
                if (false === strpos($cfgLine, $routeRcName.':')
                    && false === strpos($cfgLine, 'resource: "'.$routeRcPath.'"')) {
                    $newContent .= $cfgLine."\n";
                }
            }
            $newContent = rtrim($newContent, "\n")."\n";
            file_put_contents($routingCfgFileAndPath, $newContent);
        }
        return true;
    }

    public function hookDisplayBackOfficeHeader()
    {
        // Use addCss : registerStylesheet is only for front controller.
        $this->context->controller->addCss($this->_path.'views/css/callbundle.css');
    }

    public function hookdisplayAdminNavBarBeforeEnd($params)
    {
        $sfContainer = SymfonyContainer::getInstance();
        return $sfContainer
            ->get('twig')
            ->render(
                '@PrestaShop/fbsample_callbundle/menu.html.twig',
                [
                    'title' => 'TrainingBundle',
                    'ctr_title' => 'Hello',
                    'ctr_url' => $sfContainer->get('router')->generate(
                        'trainingbundle_hello',
                        array(),
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                ]
            );
    }
}
