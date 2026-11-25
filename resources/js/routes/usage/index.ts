import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\UsageController::__invoke
* @see app/Http/Controllers/UsageController.php:13
* @route '/usage'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/usage',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\UsageController::__invoke
* @see app/Http/Controllers/UsageController.php:13
* @route '/usage'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\UsageController::__invoke
* @see app/Http/Controllers/UsageController.php:13
* @route '/usage'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\UsageController::__invoke
* @see app/Http/Controllers/UsageController.php:13
* @route '/usage'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\UsageController::__invoke
* @see app/Http/Controllers/UsageController.php:13
* @route '/usage'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\UsageController::__invoke
* @see app/Http/Controllers/UsageController.php:13
* @route '/usage'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\UsageController::__invoke
* @see app/Http/Controllers/UsageController.php:13
* @route '/usage'
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

const usage = {
    index: Object.assign(index, index),
}

export default usage