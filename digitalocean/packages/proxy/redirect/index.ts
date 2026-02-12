interface HttpRequest {
  method: string;
  path: string;
  headers: Record<string, string>;
  body?: string;
  queryString?: string;
}

interface HttpResponse {
  statusCode: number;
  headers?: Record<string, string>;
  body?: string;
}

export async function main(args: HttpRequest): Promise<HttpResponse> {
  const targetBase = 'https://notifications.zig.tickets';
  const path = args.path || '/';
  const queryString = args.queryString ? `?${args.queryString}` : '';
  const targetUrl = `${targetBase}${path}${queryString}`;

  const headers: Record<string, string> = { ...args.headers };
  delete headers['host'];
  delete headers['Host'];
  delete headers['content-length'];
  delete headers['Content-Length'];

  const fetchOptions: RequestInit = {
    method: args.method || 'GET',
    headers,
  };

  if (args.method !== 'GET' && args.method !== 'HEAD' && args.body) {
    fetchOptions.body = args.body;
  }

  try {
    const response = await fetch(targetUrl, fetchOptions);
    const body = await response.text();

    const responseHeaders: Record<string, string> = {};
    response.headers.forEach((value, key) => {
      const lowerKey = key.toLowerCase();
      if (!['transfer-encoding', 'content-encoding', 'connection'].includes(lowerKey)) {
        responseHeaders[key] = value;
      }
    });

    return {
      statusCode: response.status,
      headers: responseHeaders,
      body,
    };
  } catch (error) {
    return {
      statusCode: 502,
      body: JSON.stringify({ error: (error as Error).message }),
    };
  }
}
