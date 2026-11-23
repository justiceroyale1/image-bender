import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ImageGenerationController::index
* @see app/Http/Controllers/ImageGenerationController.php:0
* @route '/generations'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/generations',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ImageGenerationController::index
* @see app/Http/Controllers/ImageGenerationController.php:0
* @route '/generations'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImageGenerationController::index
* @see app/Http/Controllers/ImageGenerationController.php:0
* @route '/generations'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImageGenerationController::index
* @see app/Http/Controllers/ImageGenerationController.php:0
* @route '/generations'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ImageGenerationController::index
* @see app/Http/Controllers/ImageGenerationController.php:0
* @route '/generations'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImageGenerationController::index
* @see app/Http/Controllers/ImageGenerationController.php:0
* @route '/generations'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImageGenerationController::index
* @see app/Http/Controllers/ImageGenerationController.php:0
* @route '/generations'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\ImageGenerationController::store
* @see app/Http/Controllers/ImageGenerationController.php:13
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
* @see app/Http/Controllers/ImageGenerationController.php:13
* @route '/generations'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImageGenerationController::store
* @see app/Http/Controllers/ImageGenerationController.php:13
* @route '/generations'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImageGenerationController::store
* @see app/Http/Controllers/ImageGenerationController.php:13
* @route '/generations'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImageGenerationController::store
* @see app/Http/Controllers/ImageGenerationController.php:13
* @route '/generations'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\ImageGenerationController::show
* @see app/Http/Controllers/ImageGenerationController.php:0
* @route '/generations/{generation}'
*/
export const show = (args: { generation: string | number } | [generation: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/generations/{generation}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ImageGenerationController::show
* @see app/Http/Controllers/ImageGenerationController.php:0
* @route '/generations/{generation}'
*/
show.url = (args: { generation: string | number } | [generation: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { generation: args }
    }

    if (Array.isArray(args)) {
        args = {
            generation: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        generation: args.generation,
    }

    return show.definition.url
            .replace('{generation}', parsedArgs.generation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImageGenerationController::show
* @see app/Http/Controllers/ImageGenerationController.php:0
* @route '/generations/{generation}'
*/
show.get = (args: { generation: string | number } | [generation: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImageGenerationController::show
* @see app/Http/Controllers/ImageGenerationController.php:0
* @route '/generations/{generation}'
*/
show.head = (args: { generation: string | number } | [generation: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ImageGenerationController::show
* @see app/Http/Controllers/ImageGenerationController.php:0
* @route '/generations/{generation}'
*/
const showForm = (args: { generation: string | number } | [generation: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImageGenerationController::show
* @see app/Http/Controllers/ImageGenerationController.php:0
* @route '/generations/{generation}'
*/
showForm.get = (args: { generation: string | number } | [generation: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImageGenerationController::show
* @see app/Http/Controllers/ImageGenerationController.php:0
* @route '/generations/{generation}'
*/
showForm.head = (args: { generation: string | number } | [generation: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const ImageGenerationController = { index, store, show }

export default ImageGenerationController