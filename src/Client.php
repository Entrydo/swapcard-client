<?php declare (strict_types=1);

namespace Entrydo\Infrastructure\Swapcard;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use Nette\Utils\Json;

class Client
{
    /** @var string */
    private $apiKey;

    /** @var bool */
    private $testMode;

    public function __construct(string $apiKey, bool $testMode)
    {
        $this->apiKey = $apiKey;
        $this->testMode = $testMode;
    }

    public function sendAttendeeRequest(AttendeeRequest $request): int
    {
        $guzzleClient = $this->createGuzzleClient();
        $body = Json::encode($request->getData());

        try {
            $response = $guzzleClient->post(Endpoint::ATTENDEE, [
                'body' => $body,
            ]);

            $result = Json::decode($response->getBody()->getContents());

            return (int) $result->id;
        } catch (ClientException $e) {
            $response = $e->getResponse();

            if ($response) {
                $result = Json::decode($response->getBody()->getContents());

                if (isset($result->data->id)) {
                    return (int) $result->data->id;
                }
            }

            throw $e;
        }
    }

    private function createGuzzleClient(): GuzzleClient
    {
        return new GuzzleClient([
            'base_uri' => $this->testMode ? Endpoint::TEST_URL : Endpoint::PRODUCTION_URL,
            'headers' => [
                'content-type' => 'application/json',
                'authorization' => 'Bearer ' . $this->apiKey,
            ],
        ]);
    }
}
