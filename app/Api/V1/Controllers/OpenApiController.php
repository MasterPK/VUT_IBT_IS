<?php
declare(strict_types=1);

namespace App\Api\V1\Controllers;


use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use Apitte\OpenApi\ISchemaBuilder;
use App\Api\V1\BaseControllers\MainController;
use App\Security\Permissions;
use Apitte\Core\Annotation\Controller\GroupPath;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\ControllerPath;
use Apitte\Core\Annotation\Controller\Response;
use Apitte\Core\Annotation\Controller\Responses;
use Apitte\Core\Annotation\Controller\Tag;

/**
 * @Tag("API Core")
 * @ControllerPath("/")
 * @author Petr Křehlík
 */
final class OpenApiController extends MainController
{

    /**
     * Get API schema.
     * @Path("/schema")
     * @Method("GET")
     * @Responses({
     *     @Response(code="200", description="Success"),
     * })
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @return ApiResponse
     */
    public function getSchema(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $openApi = $this->schemaBuilder->build();
        return $response->writeJsonBody($openApi->toArray());
    }

    /**
     * Get API schema.
     * @Path("/status")
     * @Method("GET")
     * @Responses({
     *     @Response(code="200", description="Success"),
     * })
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @return ApiResponse
     */
    public function getStatus(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        return $response->writeJsonBody(["s" => "ok"]);
    }

}