<?php

namespace App\Http\Controllers;

use App\Actions\Credits\CalculateAvailableCredits;
use App\Actions\Credits\CreateCreditPurchase;
use App\Exceptions\Credits\DuplicateCreditPurchased;
use App\Http\Requests\Credits\StoreCreditPurchaseRequest;
use App\Http\Resources\CreditPurchaseResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CreditController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, CalculateAvailableCredits $action): Response
    {
        $purchases = $request->user()
            ->creditPurchases()
            ->latest()
            ->paginate(10);

        return Inertia::render('credits/Index', [
            'availableCredits' => $action->forUser($request->user()),
            'purchases' => CreditPurchaseResource::collection($purchases),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCreditPurchaseRequest $request, CreateCreditPurchase $action): RedirectResponse
    {
        try {
            $action->execute($request->referenceId(), $request->amount(), $request->user());
        } catch (DuplicateCreditPurchased $e) {
            return back()->withErrors([
                'reference_id' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            logger()->critical($e->getMessage(), $e->getTrace());
            return back()->withErrors([
                'general' => 'An error occurred while processing your credit purchase. Please try again later.',
            ]);
        }

        return to_route('credits.index');
    }
}
