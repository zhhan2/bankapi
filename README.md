# bankapi
This is a practice project using PHP to build a set of API to simulate the functionality of a basic bank account.
API List:
POST /account/create
{code}
{
  name: String,
  id: String
}
{code}
POST /account/close
{code}
{
  accountId: String
}
{code}
POST /account/withdraw
{code}
{
  accountId: String,
  amount: Number // positive
}
{code}
POST /account/deposit
{code}
{
  accountId: String,
  amount: Number // positive
}
{code}
POST /account/transfer
{code}
{
  fromAccountId: String,
  toAccountId: String,
  amount: Number // positive
}
{code}
