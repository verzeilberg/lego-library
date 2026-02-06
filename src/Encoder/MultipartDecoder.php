<?php
namespace App\Encoder;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Encoder\DecoderInterface;

final class MultipartDecoder implements DecoderInterface
{
    public const FORMAT = 'multipart';

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function decode(string $data, string $format, array $context = []): ?array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return null;
        }

        return array_map(static function ($element) {
                // Only decode if it’s a string that looks like JSON
                if (is_string($element) && (str_starts_with($element, '{') || str_starts_with($element, '[') || $element === 'true' || $element === 'false' || is_numeric($element))) {
                    try {
                        return json_decode($element, true, flags: \JSON_THROW_ON_ERROR);
                    } catch (\JsonException $e) {
                        return $element; // fallback to original string
                    }
                }
                return $element;
            }, $request->request->all()) + $request->files->all();
    }

    public function supportsDecoding(string $format): bool
    {
        return self::FORMAT === $format;
    }
}
