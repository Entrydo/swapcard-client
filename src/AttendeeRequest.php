<?php declare (strict_types=1);

namespace Entrydo\Infrastructure\Swapcard;

use Nette\Utils\Validators;

class AttendeeRequest
{
    public static $allowedLanguages = ['en', 'fr'];

    /** @var string */
    private $firstName;

    /** @var string */
    private $lastName;

    /** @var string */
    private $email;

    /** @var string */
    private $language;

    /** @var string|null */
    private $password;

    /** @var string|null */
    private $jobTitle;

    /** @var string|null */
    private $jobTitle2;

    /** @var string|null */
    private $company;

    /** @var array|null */
    private $keywords;

    /** @var string|null */
    private $photo;

    /** @var string|null */
    private $mobilePhone;

    /** @var string|null */
    private $landLinePhone;

    /** @var string|null */
    private $address;

    /** @var string|null */
    private $zipCode;

    /** @var string|null */
    private $city;

    /** @var string|null */
    private $country;

    /** @var string|null */
    private $websiteUrl;

    /** @var string|null */
    private $biography;

    /** @var array|null */
    private $metadata;

    public function __construct(
        string $firstName,
        string $lastName,
        string $email,
        string $language,
        ?string $password = null,
        ?string $jobTitle = null,
        ?string $jobTitle2 = null,
        ?string $company = null,
        ?array $keywords = null,
        ?string $photo = null,
        ?string $mobilePhone = null,
        ?string $landLinePhone = null,
        ?string $address = null,
        ?string $zipCode = null,
        ?string $city = null,
        ?string $country = null,
        ?string $websiteUrl = null,
        ?string $biography = null,
        ?array $metadata = null
    ){
        if ($websiteUrl && strpos($websiteUrl, 'http') === FALSE) {
            $websiteUrl = 'http://' . $websiteUrl;
        }

        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->language = $language;
        $this->password = $password;
        $this->jobTitle = $jobTitle;
        $this->jobTitle2 = $jobTitle2;
        $this->company = $company;
        $this->keywords = $keywords;
        $this->photo = $photo;
        $this->mobilePhone = $mobilePhone;
        $this->landLinePhone = $landLinePhone;
        $this->address = $address;
        $this->zipCode = $zipCode;
        $this->city = $city;
        $this->country = $country;
        $this->websiteUrl = $websiteUrl;
        $this->biography = $biography;
        $this->metadata = $metadata;

        $this->assertCountryIsValid();
    }

    public function getData(): array
    {
        $data = [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'password' => $this->password,
            'job_title' => $this->jobTitle,
            'job_title_2' => $this->jobTitle2,
            'company' => $this->company,
            'keywords' => $this->keywords,
            'photo' => $this->photo,
            'mobile_phone' => $this->mobilePhone,
            'landline_phone' => $this->landLinePhone,
            'address' => $this->address,
            'zip_code' => $this->zipCode,
            'city' => $this->city,
            'country' => $this->country,
            'language' => $this->language,
            'website_url' => Validators::isUrl($this->websiteUrl) ? $this->websiteUrl : null,
            'biography' => $this->biography,
            'metadata' => $this->metadata,
        ];

        $data = array_filter($data, function($item) {
            return $item !== null;
        });

        return $data;
    }

    private function assertCountryIsValid(): void
    {
        if ($this->language && ! in_array($this->language, self::$allowedLanguages, TRUE)) {
            $allowedLanguages = "'" . implode("', '", self::$allowedLanguages) . "'";

            throw new \InvalidArgumentException("Language '{$this->language}' is not valid language. Valid languages are ${allowedLanguages}.");
        }
    }
}
