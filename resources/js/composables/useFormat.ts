export function useFormat() {
    const currencyFormatter = Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 9
    })

    const numberFormatter = Intl.NumberFormat('en-US', {
        style: 'decimal',
        maximumFractionDigits: 0
    })

    const currencyFormat = (amount: number | string) => {
        return currencyFormatter.format(Number(amount));
    }

    const numberFormat = (amount: number | string) => {
        return numberFormatter.format(Number(amount));
    }

    return {
        currencyFormat,
        numberFormat
    }
}