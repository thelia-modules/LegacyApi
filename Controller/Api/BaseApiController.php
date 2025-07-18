<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace LegacyApi\Controller\Api;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Thelia\Controller\BaseController;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Response;
use LegacyApi\Model\Api;
use Thelia\Core\Template\ParserInterface;
use Thelia\Form\BaseForm;

/**
 * Class BaseApiController
 * @package LegacyApi\Controller\Api
 * @author Manuel Raynaud <manu@raynaud.io>
 */
class BaseApiController extends BaseController
{
    const CONTROLLER_TYPE = 'api';

    const EMPTY_FORM_NAME = "thelia.api.empty";

    protected $apiUser;
    protected $currentRouter = "router.api";

    protected function checkAuth($resources, $modules, $accesses)
    {
        $resources = is_array($resources) ? $resources : array($resources);
        $modules = is_array($modules) ? $modules : array($modules);
        $accesses = is_array($accesses) ? $accesses : array($accesses);

        if (true !== $this->getSecurityContext()->isUserGranted(array("API"), $resources, $modules, $accesses, $this->getApiUser())) {
            throw new AccessDeniedHttpException();
        }
    }

    public function setApiUser(Api $apiUser)
    {
        $this->apiUser = $apiUser;
    }

    public function getApiUser()
    {
        return $this->apiUser;
    }

    /**
     * @param null|mixed $template
     * @return ParserInterface instance parser
     */
    protected function getParser($template = null)
    {
        throw new \RuntimeException("The parser is not available in an API controller");
    }

    /**
     * Render the given template, and returns the result as an Http Response.
     *
     * @param mixed $content the response content
     * @param array $args   the template arguments
     * @param int   $status http code status
     * @param array $headers The HTTP headers of the response
     * @return \Thelia\Core\HttpFoundation\Response
     */
    protected function render($templateName, $args = array(), $status = 200, $headers = array())
    {
        return new Response($this->renderRaw($templateName), $status, $headers);
    }

    /**
     * Render the given template, and returns the result as a string.
     *
     * @param mixed $templateName
     * @param array $args        the template arguments
     * @param null  $templateDir
     *
     * @return string
     */
    protected function renderRaw($templateName, $args = array(), $templateDir = null)
    {
        if (is_array($templateName)) {
            $templateName = json_encode($templateName);
        }

        return $templateName;
    }

    /**
     * @param $name
     * @param string $type
     * @param array $data
     * @param array $options
     * @return \Thelia\Form\BaseForm
     *
     * Deactivate csrf token by default on API
     */
    public function createForm($name, $type = FormType::class, array $data = array(), array $options = array())
    {
        $options = array_merge(
            [
                "csrf_protection" => false,
            ],
            $options
        );

        return parent::createForm($name, $type, $data, $options);
    }

    /**
     * @return string
     */
    public function getControllerType(): string
    {
        return self::CONTROLLER_TYPE;
    }

    protected function submitAndValidateForm(BaseForm $aBaseForm, Request $request)
    {
        $form = $aBaseForm->getForm();

        if (! $form->isSubmitted()) {
            $form->submit($request->request->all());
        }

        return $this->validateForm($aBaseForm);
    }
}
