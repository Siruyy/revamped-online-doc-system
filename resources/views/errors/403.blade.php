@include('errors.layout', [
    'code' => '403',
    'title' => 'Access not allowed',
    'message' => 'Your account does not have permission to open this page. Sign in with the correct role or return to the portal home.',
])
