# Weirdo Labs

Usa el componente Symfony Workflow en Laravel.

### Instalación

Agregue un ServiceProvider a su matriz de proveedores en `config/app.php`:

```php
<?php

'providers' => [
    ...
    Weirdo\LaravelWorkflow\WorkflowServiceProvider::class,

]
```

Agregue la fachada Workflow a su matriz de fachadas:

```php
<?php
    ...
    'Workflow' => Weirdo\LaravelWorkflow\Facades\WorkflowFacade::class,
```

Publicar el archivo de configuración

```bash
php artisan vendor:publish --provider="Weirdo\LaravelWorkflow\WorkflowServiceProvider"
```

Configura tu workflow en `config/workflow.php`

```php
<?php

// Full workflow, annotated.
return [
    // Name of the workflow is the key
    'straight' => [
        'type' => 'workflow', // or 'state_machine', defaults to 'workflow' if omitted
        // The marking store can be omitted, and will default to 'multiple_state'
        // for workflow and 'single_state' for state_machine if the type is omitted
        'marking_store' => [
            'type' => 'multiple_state', // or 'single_state', can be omitted to default to workflow type's default
            'property' => 'marking', // this is the property on the model, defaults to 'marking'
            'class' => MethodMarkingStore::class, // optional, uses EloquentMethodMarkingStore by default (for Eloquent models)
        ],
        // optional top-level metadata
        'metadata' => [
            // any data
        ],
        'supports' => ['App\BlogPost'], // objects this workflow supports
        // Specifies events to dispatch (only in 'workflow', not 'state_machine')
        // - set `null` to dispatch all events (default, if omitted)
        // - set to empty array (`[]`) to dispatch no events
        // - set to array of events to dispatch only specific events
        // Note that announce will dispatch a guard event on the next transition
        // (if announce isn't dispatched the next transition won't guard until checked/applied)
        'events_to_dispatch' => [
           Symfony\Component\Workflow\WorkflowEvents::ENTER,
           Symfony\Component\Workflow\WorkflowEvents::LEAVE,
           Symfony\Component\Workflow\WorkflowEvents::TRANSITION,
           Symfony\Component\Workflow\WorkflowEvents::ENTERED,
           Symfony\Component\Workflow\WorkflowEvents::COMPLETED,
           Symfony\Component\Workflow\WorkflowEvents::ANNOUNCE,
        ],
        'places' => ['draft', 'review', 'rejected', 'published'],
        'initial_places' => ['draft'], // defaults to the first place if omitted
        'transitions' => [
            'to_review' => [
                'from' => 'draft',
                'to' => 'review',
                // optional transition-level metadata
                'metadata' => [
                    // any data
                ]
            ],
            'publish' => [
                'from' => 'review',
                'to' => 'published'
            ],
            'reject' => [
                'from' => 'review',
                'to' => 'rejected'
            ]
        ],
    ]
];
```

Una configuración más mínima (para un flujo de trabajo en un modelo elocuente).

```php
<?php

// Simple workflow. Sets type 'workflow', with a 'multiple_state' marking store
// on the 'marking' property of any 'App\BlogPost' model.
return [
    'simple' => [
        'supports' => ['App\BlogPost'], // objects this workflow supports
        'places' => ['draft', 'review', 'rejected', 'published'],
        'transitions' => [
            'to_review' => [
                'from' => 'draft',
                'to' => 'review'
            ],
            'publish' => [
                'from' => 'review',
                'to' => 'published'
            ],
            'reject' => [
                'from' => 'review',
                'to' => 'rejected'
            ]
        ],
    ]
];
```

Si está utilizando un tipo de "multiple_state" de store_marking (es decir, estará en varios lugares simultáneamente en su flujo de trabajo), necesitará su clase compatible/modelo Eloquent para convertir el marcado en una matriz. Lea más en los [documentos de Laravel](https://laravel.com/docs/5.8/eloquent-mutators#array-and-json-casting).


También puede agregar metadatos, similar a la implementación de Symfony (nota: no se recopila de la misma manera que la implementación de Symfony, pero debería funcionar de la misma manera. Abra una solicitud de extracción o un problema si ese no es el caso).

```php
<?php

return [
    'straight' => [
        'type' => 'workflow', // or 'state_machine'
        'metadata' => [
            'title' => 'Blog Publishing Workflow',
        ],
        'marking_store' => [
            'type' => 'multiple_state', // or 'single_state'
            'property' => 'currentPlace' // this is the property on the model
        ],
        'supports' => ['App\BlogPost'],
        'places' => [
            'draft' => [
                'metadata' => [
                    'max_num_of_words' => 500,
                ]
            ]
            'review',
            'rejected',
            'published'
        ],
        'transitions' => [
            'to_review' => [
                'from' => 'draft',
                'to' => 'review',
                'metadata' => [
                    'priority' => 0.5,
                ]
            ],
            'publish' => [
                'from' => 'review',
                'to' => 'published'
            ],
            'reject' => [
                'from' => 'review',
                'to' => 'rejected'
            ]
        ],
    ]
];
```

Use ```WorkflowTrait``` dentro de las clases admitidas

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Weirdo\LaravelWorkflow\Traits\WorkflowTrait;

class BlogPost extends Model
{
  use WorkflowTrait;

}
```
## Usage

```php
<?php

use App\BlogPost;
use Workflow;

$post = BlogPost::find(1);
$workflow = Workflow::get($post);
// if more than one workflow is defined for the BlogPost class
$workflow = Workflow::get($post, $workflowName);
// or get it directly from the trait
$workflow = $post->workflow_get();
// if more than one workflow is defined for the BlogPost class
$workflow = $post->workflow_get($workflowName);

$workflow->can($post, 'publish'); // False
$workflow->can($post, 'to_review'); // True
$transitions = $workflow->getEnabledTransitions($post);

// Apply a transition
$workflow->apply($post, 'to_review');
$post->save(); // Don't forget to persist the state

// Get the workflow directly

// Using the WorkflowTrait
$post->workflow_can('publish'); // True
$post->workflow_can('to_review'); // False

// Get the post transitions
foreach ($post->workflow_transitions() as $transition) {
    echo $transition->getName();
}

// Apply a transition
$post->workflow_apply('publish');
$post->save();
```

## Symfony Workflow

Una vez que tengas el componente de flujo de trabajo subyacente de Symfony, puedes hacer lo que quieras, tal como lo harías en Symfony. A continuación se proporcionan un par de ejemplos, pero asegúrate de echar un vistazo a los [documentos de Symfony](https://symfony.com/doc/current/workflow.html) para comprender mejor lo que sucede aquí.

```php
<?php

use App\Blogpost;
use Workflow;

$post = BlogPost::find(1);
$workflow = $post->workflow_get();

// Get the current places
$places = $workflow->getMarking($post)->getPlaces();

// Get the definition
$definition = $workflow->getDefinition();

// Get the metadata
$metadata = $workflow->getMetadataStore();
// or get a specific piece of metadata
$workflowMetadata = $workflow->getMetadataStore()->getWorkflowMetadata();
$placeMetadata = $workflow->getMetadataStore()->getPlaceMetadata($place); // string place name
$transitionMetadata = $workflow->getMetadataStore()->getTransitionMetadata($transition); // transition object
// or by key
$otherPlaceMetadata = $workflow->getMetadataStore()->getMetadata('max_num_of_words', 'draft');
```

### Usa los eventos

Este paquete proporciona una lista de eventos activados durante una transición.

```php
    Weirdo\LaravelWorkflow\Events\Guard
    Weirdo\LaravelWorkflow\Events\Leave
    Weirdo\LaravelWorkflow\Events\Transition
    Weirdo\LaravelWorkflow\Events\Enter
    Weirdo\LaravelWorkflow\Events\Entered
```

Puedes suscribirte a un evento

```php
<?php

namespace App\Listeners;

use Weirdo\LaravelWorkflow\Events\GuardEvent;

class BlogPostWorkflowSubscriber
{
    /**
     * Handle workflow guard events.
     */
    public function onGuard(GuardEvent $event)
    {
        /** Symfony\Component\Workflow\Event\GuardEvent */
        $originalEvent = $event->getOriginalEvent();

        /** @var App\BlogPost $post */
        $post = $originalEvent->getSubject();
        $title = $post->title;

        if (empty($title)) {
            // Posts with no title should not be allowed
            $originalEvent->setBlocked(true);
        }
    }

    /**
     * Handle workflow leave event.
     */
    public function onLeave($event)
    {
        // The event can also proxy to the original event
        $subject = $event->getSubject();
        // is the same as:
        $subject = $event->getOriginalEvent()->getSubject();
    }

    /**
     * Handle workflow transition event.
     */
    public function onTransition($event) {}

    /**
     * Handle workflow enter event.
     */
    public function onEnter($event) {}

    /**
     * Handle workflow entered event.
     */
    public function onEntered($event) {}

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'Weirdo\LaravelWorkflow\Events\GuardEvent',
            'App\Listeners\BlogPostWorkflowSubscriber@onGuard'
        );

        $events->listen(
            'Weirdo\LaravelWorkflow\Events\LeaveEvent',
            'App\Listeners\BlogPostWorkflowSubscriber@onLeave'
        );

        $events->listen(
            'Weirdo\LaravelWorkflow\Events\TransitionEvent',
            'App\Listeners\BlogPostWorkflowSubscriber@onTransition'
        );

        $events->listen(
            'Weirdo\LaravelWorkflow\Events\EnterEvent',
            'App\Listeners\BlogPostWorkflowSubscriber@onEnter'
        );

        $events->listen(
            'Weirdo\LaravelWorkflow\Events\EnteredEvent',
            'App\Listeners\BlogPostWorkflowSubscriber@onEntered'
        );
    }

}
```

### Dump Workflows

El flujo de trabajo de Symfony usa GraphvizDumper para crear la imagen del flujo de trabajo. Es posible que deba instalar el comando punto de [Graphviz](http://www.graphviz.org/)

```php
php artisan workflow:dump workflow_name --class App\\BlogPost
```

Puede cambiar el formato de la imagen con la opción --format. Por defecto el formato es png.

```php
php artisan workflow:dump workflow_name --format=jpg
```