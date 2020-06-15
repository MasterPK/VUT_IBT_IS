<?php
declare(strict_types=1);

namespace App\Api\V1\Controllers;

use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\GroupPath;
use Apitte\Core\Annotation\Controller\ControllerPath;
use Apitte\Core\Annotation\Controller\RequestParameter;
use Apitte\Core\Annotation\Controller\RequestParameters;
use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use Exception;
use App\Api\V1\BaseControllers\BaseV1Controller;

/**
 *
 * @ControllerPath("/station")
 */
final class StationController extends BaseV1Controller
{
    /**
     * Check params stationId and stationToken
     * @param ApiRequest $request
     */
    private function checkStationTokens(ApiRequest $request)
    {
        try {
            $stationToken = $request->getParameter("stationToken");
            $idStation = $request->getParameter("stationId");
        } catch (Exception $e) {
            throw new ClientErrorException($e->getMessage(), 400);
        }


        $station = $this->orm->stations->getBy(["apiToken" => $stationToken, "id" => $idStation]);

        if (!$station) {
            throw new ClientErrorException("Station does not exist or ID and token are not valid!", 404);
        }
    }

    /**
     * Get data about station specified by Id and API token.
     * @Path("/")
     * @Method("GET")
     * @RequestParameters({
     * 		@RequestParameter(name="stationId", type="int", description="Station Id", in="query"),
     * 		@RequestParameter(name="stationToken", type="string", description="Station API token", in="query")
     * })
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @return ApiResponse
     */
    public function getStation(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $this->checkStationTokens($request);
        $station = $this->orm->stations->getById($request->getParameter("stationId"));
        return $response->writeJsonBody($station->toArray());
    }

    /**
     * Get all stations. Admin user token required.
     * @Path("/all")
     * @Method("GET")
     * @RequestParameters({
     * 		@RequestParameter(name="userToken", type="string", description="User token", in="query")
     * })
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @return ApiResponse
     */
    public function getStations(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $this->checkUserPermission($request);
        $stations = $this->orm->stations->findAll()->fetchAll();
        $result=[];
        foreach ($stations as $station)
        {
            array_push($result,$station->toArray());
        }
        return $response->writeJsonBody($result);
    }

}