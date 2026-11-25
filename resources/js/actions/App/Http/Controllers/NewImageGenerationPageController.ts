import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\NewImageGenerationPageController::__invoke
* @see app/Http/Controllers/NewImageGenerationPageController.php:10
* @route '/generations/new'
*/
const NewImageGenerationPageController = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: NewImageGenerationPageController.url(options),
    method: 'get',
})

NewImageGenerationPageController.definition = {
    methods: ["get","head"],
    url: '/generations/new',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\NewImageGenerationPageController::__invoke
* @see app/Http/Controllers/NewImageGenerationPageController.php:10
* @route '/generations/new'
*/
NewImageGenerationPageController.url = (options?: RouteQueryOptions) => {
    return NewImageGenerationPageController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NewImageGenerationPageController::__invoke
* @see app/Http/Controllers/NewImageGenerationPageController.php:10
* @route '/generations/new'
*/
NewImageGenerationPageController.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: NewImageGenerationPageController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NewImageGenerationPageController::__invoke
* @see app/Http/Controllers/NewImageGenerationPageController.php:10
* @route '/generations/new'
*/
NewImageGenerationPageController.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: NewImageGenerationPageController.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\NewImageGenerationPageController::__invoke
* @see app/Http/Controllers/NewImageGenerationPageController.php:10
* @route '/generations/new'
*/
const NewImageGenerationPageControllerForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: NewImageGenerationPageController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NewImageGenerationPageController::__invoke
* @see app/Http/Controllers/NewImageGenerationPageController.php:10
* @route '/generations/new'
*/
NewImageGenerationPageControllerForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: NewImageGenerationPageController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NewImageGenerationPageController::__invoke
* @see app/Http/Controllers/NewImageGenerationPageController.php:10
* @route '/generations/new'
*/
NewImageGenerationPageControllerForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: NewImageGenerationPageController.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

NewImageGenerationPageController.form = NewImageGenerationPageControllerForm

export default NewImageGenerationPageController