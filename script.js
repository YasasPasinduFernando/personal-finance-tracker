// script.js
document.addEventListener('DOMContentLoaded', function() {
    fetchTransactions();
});

function fetchTransactions() {
    fetch('get_transactions.php')
        .then(response => response.json())
        .then(data => {
            const transactionsDiv = document.getElementById('transactions');
            transactionsDiv.innerHTML = '';
            data.forEach(transaction => {
                transactionsDiv.innerHTML += `
                    <div class="border p-4 mb-2">
                        <p><strong>Amount:</strong> $${transaction.amount}</p>
                        <p><strong>Category:</strong> ${transaction.category}</p>
                        <p><strong>Date:</strong> ${transaction.date}</p>
                    </div>
                `;
            });
        });
}