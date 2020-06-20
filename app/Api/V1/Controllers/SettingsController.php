<?php


namespace App\Api\V1\Controllers;

use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\GroupPath;
use Apitte\Core\Annotation\Controller\ControllerPath;
use Apitte\Core\Annotation\Controller\RequestParameter;
use Apitte\Core\Annotation\Controller\RequestParameters;
use Apitte\Core\Annotation\Controller\Request;
use Apitte\Core\Annotation\Controller\Responses;
use Apitte\Core\Annotation\Controller\Response;
use Apitte\Core\Annotation\Controller\RequestMapper;
use Apitte\Core\Annotation\Controller\Tag;
use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\Api\V1\BaseControllers\BaseV1Controller;
use App\Models\Orm\Settings\Setting;
use App\Models\Orm\StationsUsers\StationsUsers;
use App\Models\Orm\Users\User;
use App\Security\Permissions;

/**
 * @Tag("Setting")
 * @ControllerPath("/setting")
 */
final class SettingsController extends BaseV1Controller
{
    /**
     * Get all settings.
     * Admin user token required.
     * @Path("/")
     * @Method("GET")
     * @RequestParameters({
     *     @RequestParameter(name="userToken", type="string", description="User API token", in="query"),
     * })
     * @Responses({
     *     @Response(code="200", description="Success"),
     *     @Response(code="400", description="Bad request"),
     *     @Response(code="403", description="Forbidden")
     * })
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @return ApiResponse
     */
    public function getStations(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $this->checkUserPermission($request, Permissions::ADMIN);

        /** @var Setting[] $setting */
        $rows=$this->orm->settings->findAll()->fetchAll();


        $settings=[];
        foreach ($rows as $row){
            array_push($settings,$row->toArray());
        }

        return $response->writeJsonBody($settings);
    }

    /**
     * Edit setting.
     * Admin user token required.
     * @Path("/{key}/{value}")
     * @Method("PUT")
     * @RequestParameters({
     *     @RequestParameter(name="userToken", type="string", description="User API token", in="query"),
     *     @RequestParameter(name="key", type="string", description="Setting key"),
     *     @RequestParameter(name="value", type="string", description="Setting value")
     * })
     * @Responses({
     *     @Response(code="200", description="Success"),
     *     @Response(code="400", description="Bad request"),
     *     @Response(code="403", description="Forbidden")
     * })
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @return ApiResponse
     */
    public function update(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $this->checkUserPermission($request, Permissions::ADMIN);

        /** @var Setting $setting */
        $setting=$this->orm->settings->getBy(["key"=>$request->getParameter("key")]);

        if(!$setting){
            throw new ClientErrorException("Setting not found!",400);
        }

        $setting->value=$request->getParameter("value");
        $this->orm->settings->persistAndFlush($setting);


        return $response->writeJsonBody(["status" => "success"]);
    }


}