export function useCurrency() {
    const formatter = Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 6
    })

    const currencyFormat = (amount: number) => {
        return formatter.format(amount);
    }

    return {
        currencyFormat
    }
}