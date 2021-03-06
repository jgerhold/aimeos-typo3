<?php

/**
 * @license GPLv3, http://www.gnu.org/copyleft/gpl.html
 * @copyright Metaways Infosystems GmbH, 2013
 * @copyright Aimeos (aimeos.org), 2014-2016
 * @package TYPO3
 */


namespace Aimeos\Aimeos\Controller;


use Aimeos\Aimeos\Base;


/**
 * Aimeos checkout controller.
 *
 * @package TYPO3
 */
class CheckoutController extends AbstractController
{
	/**
	 * Processes requests and renders the checkout process.
	 */
	public function indexAction()
	{
		$templatePaths = Base::getAimeos()->getCustomPaths( 'client/html' );
		$client = \Aimeos\Client\Html\Checkout\Standard\Factory::createClient( $this->getContext(), $templatePaths );

		return $this->getClientOutput( $client );
	}


	/**
	 * Processes requests and renders the checkout confirmation.
	 */
	public function confirmAction()
	{
		$context = $this->getContext();
		$templatePaths = Base::getAimeos()->getCustomPaths( 'client/html' );
		$client = \Aimeos\Client\Html\Checkout\Confirm\Factory::createClient( $context, $templatePaths );

		$view = $context->getView();
		$param = array_merge( \TYPO3\CMS\Core\Utility\GeneralUtility::_GET(), \TYPO3\CMS\Core\Utility\GeneralUtility::_POST() );
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $view, $param );
		$view->addHelper( 'param', $helper );

		$client->setView( $view );
		$client->process();

		$this->response->addAdditionalHeaderData( (string) $client->getHeader() );
	
		$aimeos = \Aimeos\Aimeos\Base::getAimeos();
		$context = \Aimeos\Aimeos\Scheduler\Base::getContext( $this->settings );
		\Aimeos\Controller\Jobs\Order\Email\Payment\Factory::createController( $context, $aimeos )->run();
		\Aimeos\Controller\Jobs\Order\Service\Delivery\Factory::createController( $context, $aimeos )->run();

		return $client->getBody();
	}


	/**
	 * Processes update requests from payment service providers.
	 */
	public function updateAction()
	{
		try
		{
			$context = $this->getContext();
			$templatePaths = Base::getAimeos()->getCustomPaths( 'client/html' );
			$client = \Aimeos\Client\Html\Checkout\Update\Factory::createClient( $context, $templatePaths );

			$view = $context->getView();
			$param = array_merge( \TYPO3\CMS\Core\Utility\GeneralUtility::_GET(), \TYPO3\CMS\Core\Utility\GeneralUtility::_POST() );
			$helper = new \Aimeos\MW\View\Helper\Param\Standard( $view, $param );
			$view->addHelper( 'param', $helper );

			$client->setView( $view );
			$client->process();

			$this->response->addAdditionalHeaderData( (string) $client->getHeader() );

			return $client->getBody();
		}
		catch( \Exception $e )
		{
			@header( 'HTTP/1.1 500 Internal server error', true, 500 );
			return 'Error: ' . $e->getMessage();
		}
	}
}
