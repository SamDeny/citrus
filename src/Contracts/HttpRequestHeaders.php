<?php declare(strict_types=1);

namespace Citrus\Contracts;

interface HttpRequestHeaders
{
    
    const REQUEST_HEADERS = [
        'A-IM',
        'Accept',
        'Accept-Charset',
        'Accept-Datetime',
        'Accept-Encoding',
        'Accept-Language',
        'Access-Control-Request-Method',
        'Access-Control-Request-Headers',
        'Authorization',
        'Cache-Control',
        'Connection',
        'Content-Encoding',
        'Content-Length',
        'Content-MD5',
        'Content-Type',
        'Cookie',
        'DNT',
        'Date',
        'Expect',
        'Forwarded',
        'From',
        'Front-End-Https',
        'HTTP2-Settings',
        'Host',
        'If-Match',
        'If-Modified-Since',
        'If-None-Match',
        'If-Range',
        'If-Unmodified-Since',
        'Max-Forwards',
        'Origin',
        'Pragma',
        'Prefer',
        'Proxy-Authorization',
        'Proxy-Connection',
        'Range',
        'Referer',
        'Referrer-Policy',
        'Save-Data',
        'Sec-Fetch-Site',
        'Sec-Fetch-Mode',
        'Sec-Fetch-User',
        'Sec-Fetch-Dest',
        'Sec-WebSocket-Key',
        'Sec-WebSocket-Extensions',
        'Sec-WebSocket-Accept',
        'Sec-WebSocket-Protocol',
        'Sec-WebSocket-Version',
        'Service-Worker-Navigation-Preload',
        'TE',
        'Trailer',
        'Transfer-Encoding',
        'Upgrade',
        'Upgrade-Insecure-Requests',
        'User-Agent',
        'Via',
        'Warning',
        'X-ATT-DeviceId',
        'X-Csrf-Token',
        'X-Forwarded-For',
        'X-Forwarded-Host',
        'X-Forwarded-Proto',
        'X-Http-Method-Override',
        'X-Requested-With',
        'X-UIDH',
        'X-Wap-Profile'
    ];

}