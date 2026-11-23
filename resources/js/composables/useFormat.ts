export function useFormat() {
    const currencyFormatter = Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 6
    })

    const numberFormatter = Intl.NumberFormat('en-US', {
        style: 'decimal',
        maximumFractionDigits: 0
    })

    const currencyFormat = (amount: number) => {
        return currencyFormatter.format(amount);
    }

    const numberFormat = (amount: number) => {
        return numberFormatter.format(amount);
    }

    return {
        currencyFormat,
        numberFormat
    }
}