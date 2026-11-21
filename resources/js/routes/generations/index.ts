import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\GenerationController::create
* @see app/Http/Controllers/GenerationController.php:12
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
* @see \App\Http\Controllers\GenerationController::create
* @see app/Http/Controllers/GenerationController.php:12
* @route '/generations/new'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\GenerationController::create
* @see app/Http/Controllers/GenerationController.php:12
* @route '/generations/new'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\GenerationController::create
* @see app/Http/Controllers/GenerationController.php:12
* @route '/generations/new'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\GenerationController::create
* @see app/Http/Controllers/GenerationController.php:12
* @route '/generations/new'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\GenerationController::create
* @see app/Http/Controllers/GenerationController.php:12
* @route '/generations/new'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\GenerationController::create
* @see app/Http/Controllers/GenerationController.php:12
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

const generations = {
    create: Object.assign(create, create),
}

export default generations