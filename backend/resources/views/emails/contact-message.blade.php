Nouveau message via « تواصل معنا » / Contact

Nom : {{ $payload['name'] }}
Email : {{ $payload['email'] }}
@if(!empty($payload['phone']))
Téléphone : {{ $payload['phone'] }}
@endif
Sujet : {{ $payload['subject'] }}

Message :
{{ $payload['message'] }}
