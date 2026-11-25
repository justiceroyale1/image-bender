import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\NewImageGenerationPageController::__invoke
* @see app/Http/Controllers/NewImageGenerationPageController.php:10
* @route '/generations/new'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/generations/new',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\NewImageGenerationPageController::__invoke
* @see app/Http/Controllers/NewImageGenerationPageController.php:10
* @route '/generations/new'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NewImageGenerationPageController::__invoke
* @see app/Http/Controllers/NewImageGenerationPageController.php:10
* @route '/generations/new'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NewImageGenerationPageController::__invoke
* @see app/Http/Controllers/NewImageGenerationPageController.php:10
* @route '/generations/new'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\NewImageGenerationPageController::__invoke
* @see app/Http/Controllers/NewImageGenerationPageController.php:10
* @route '/generations/new'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NewImageGenerationPageController::__invoke
* @see app/Http/Controllers/NewImageGenerationPageController.php:10
* @route '/generations/new'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NewImageGenerationPageController::__invoke
* @see app/Http/Controllers/NewImageGenerationPageController.php:10
* @route '/generations/new'
*/
createForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

create.form = createForm

/**
* @see \App\Http\Controllers\ImageGenerationController::store
* @see app/Http/Controllers/ImageGenerationController.php:28
* @route '/generations'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/generations',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ImageGenerationController::store
* @see app/Http/Controllers/ImageGenerationController.php:28
* @route '/generations'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImageGenerationController::store
* @see app/Http/Controllers/ImageGenerationController.php:28
* @route '/generations'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImageGenerationController::store
* @see app/Http/Controllers/ImageGenerationController.php:28
* @route '/generations'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImageGenerationController::store
* @see app/Http/Controllers/ImageGenerationController.php:28
* @route '/generations'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\ImageGenerationController::show
* @see app/Http/Controllers/ImageGenerationController.php:37
* @route '/generations/{generation}'
*/
export const show = (args: { generation: string | { ulid: string } } | [generation: string | { ulid: string } ] | string | { ulid: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/generations/{generation}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ImageGenerationController::show
* @see app/Http/Controllers/ImageGenerationController.php:37
* @route '/generations/{generation}'
*/
show.url = (args: { generation: string | { ulid: string } } | [generation: string | { ulid: string } ] | string | { ulid: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { generation: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'ulid' in args) {
        args = { generation: args.ulid }
    }

    if (Array.isArray(args)) {
        args = {
            generation: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        generation: typeof args.generation === 'object'
        ? args.generation.ulid
        : args.generation,
    }

    return show.definition.url
            .replace('{generation}', parsedArgs.generation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImageGenerationController::show
* @see app/Http/Controllers/ImageGenerationController.php:37
* @route '/generations/{generation}'
*/
show.get = (args: { generation: string | { ulid: string } } | [generation: string | { ulid: string } ] | string | { ulid: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImageGenerationController::show
* @see app/Http/Controllers/ImageGenerationController.php:37
* @route '/generations/{generation}'
*/
show.head = (args: { generation: string | { ulid: string } } | [generation: string | { ulid: string } ] | string | { ulid: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ImageGenerationController::show
* @see app/Http/Controllers/ImageGenerationController.php:37
* @route '/generations/{generation}'
*/
const showForm = (args: { generation: string | { ulid: string } } | [generation: string | { ulid: string } ] | string | { ulid: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImageGenerationController::show
* @see app/Http/Controllers/ImageGenerationController.php:37
* @route '/generations/{generation}'
*/
showForm.get = (args: { generation: string | { ulid: string } } | [generation: string | { ulid: string } ] | string | { ulid: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImageGenerationController::show
* @see app/Http/Controllers/ImageGenerationController.php:37
* @route '/generations/{generation}'
*/
showForm.head = (args: { generation: string | { ulid: string } } | [generation: string | { ulid: string } ] | string | { ulid: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const generations = {
    create: Object.assign(create, create),
    store: Object.assign(store, store),
    show: Object.assign(show, show),
}

export default generations